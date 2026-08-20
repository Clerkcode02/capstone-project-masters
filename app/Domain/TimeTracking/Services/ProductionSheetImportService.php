<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Imports\ProductionSheetImport;
use App\Models\Account;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductionSheetImportService
{
    private const REQUIRED_COLUMNS = ['employee_code', 'log_date', 'hour_type', 'duration_minutes'];

    /**
     * Parse an uploaded production sheet and split rows into valid/rejected sets.
     *
     * @return array{valid: array<int, array<string, mixed>>, rejected: array<int, array<string, mixed>>}
     */
    public function parse(string $storedPath): array
    {
        $import = new ProductionSheetImport;
        Excel::import($import, Storage::disk('local')->path($storedPath));
        $rows = $import->rows();

        $users = User::query()->whereNotNull('employee_code')->get()->keyBy('employee_code');
        $accounts = Account::query()->get()->keyBy('code');

        $valid = [];
        $rejected = [];
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2; // account for the heading row
            $row = $this->normalizeRow($row);
            $errors = $this->validateRow($row);

            $userCode = $row['employee_code'] ?? null;
            $user = $userCode !== null ? $users->get($userCode) : null;

            if ($userCode !== null && ! $user) {
                $errors[] = "Unknown or inactive employee_code '{$userCode}'.";
            } elseif ($user && ! $user->is_active) {
                $errors[] = "Employee '{$userCode}' is not active.";
            }

            $accountCode = $row['account_code'] ?? null;
            $account = null;
            if ($accountCode !== null && $accountCode !== '') {
                $account = $accounts->get($accountCode);
                if (! $account) {
                    $errors[] = "Unknown account_code '{$accountCode}'.";
                }
            } elseif (($row['hour_type'] ?? null) === HourType::Production->value) {
                $errors[] = 'account_code is required for production hours.';
            }

            if ($errors === [] && $user) {
                $dedupeKey = implode('|', [$user->id, $row['log_date'], $account?->id, $row['hour_type']]);

                if (isset($seenInFile[$dedupeKey])) {
                    $errors[] = 'Duplicate row within this file (same employee, date, account, hour type).';
                } else {
                    $seenInFile[$dedupeKey] = true;

                    $duplicateExists = TimeLog::query()
                        ->where('user_id', $user->id)
                        ->whereDate('log_date', $row['log_date'])
                        ->where('account_id', $account?->id)
                        ->where('hour_type', $row['hour_type'])
                        ->exists();

                    if ($duplicateExists) {
                        $errors[] = 'Duplicate of an existing time log (same employee, date, account, hour type).';
                    }
                }
            }

            if ($errors !== []) {
                $rejected[] = [
                    'row' => $lineNumber,
                    'data' => $row,
                    'reasons' => $errors,
                ];

                continue;
            }

            $valid[] = [
                'row' => $lineNumber,
                'user_id' => $user->id,
                'employee_code' => $user->employee_code,
                'employee_name' => $user->full_name,
                'account_id' => $account?->id,
                'account_code' => $account?->code,
                'log_date' => $row['log_date'],
                'hour_type' => $row['hour_type'],
                'duration_minutes' => (int) $row['duration_minutes'],
                'notes' => $row['notes'] !== '' ? $row['notes'] : null,
            ];
        }

        return ['valid' => $valid, 'rejected' => $rejected];
    }

    /**
     * @param  array<int, array<string, mixed>>  $validRows
     */
    public function commit(array $validRows, User $importer): int
    {
        $now = Carbon::now();

        $records = array_map(static fn (array $row) => [
            'user_id' => $row['user_id'],
            'task_id' => null,
            'account_id' => $row['account_id'],
            'log_date' => $row['log_date'],
            'hour_type' => $row['hour_type'],
            'duration_minutes' => $row['duration_minutes'],
            'entry_method' => EntryMethod::Import->value,
            'notes' => $row['notes'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $validRows);

        DB::transaction(function () use ($records): void {
            foreach (array_chunk($records, 200) as $chunk) {
                TimeLog::query()->insert($chunk);
            }
        });

        return count($records);
    }

    /**
     * @return array<string, string>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [
            'employee_code' => trim((string) ($row['employee_code'] ?? '')),
            'log_date' => trim((string) ($row['log_date'] ?? '')),
            'account_code' => trim((string) ($row['account_code'] ?? '')),
            'hour_type' => trim((string) ($row['hour_type'] ?? '')),
            'duration_minutes' => trim((string) ($row['duration_minutes'] ?? '')),
            'notes' => trim((string) ($row['notes'] ?? '')),
        ];

        if ($normalized['log_date'] !== '') {
            try {
                $normalized['log_date'] = Carbon::parse($normalized['log_date'])->toDateString();
            } catch (\Throwable) {
                // leave as-is; validateRow() will flag the malformed date
            }
        }

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function validateRow(array $row): array
    {
        $errors = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (($row[$column] ?? '') === '') {
                $errors[] = "Missing required field '{$column}'.";
            }
        }

        if (($row['log_date'] ?? '') !== '') {
            if (! $this->isValidDate($row['log_date'])) {
                $errors[] = "log_date '{$row['log_date']}' is not a valid date (expected YYYY-MM-DD).";
            } elseif (Carbon::parse($row['log_date'])->isAfter(Carbon::today())) {
                $errors[] = 'log_date cannot be in the future.';
            }
        }

        if (($row['hour_type'] ?? '') !== '' && HourType::tryFrom($row['hour_type']) === null) {
            $errors[] = "hour_type '{$row['hour_type']}' must be one of: production, non_production, leave.";
        }

        if (($row['duration_minutes'] ?? '') !== '') {
            if (! ctype_digit($row['duration_minutes'])) {
                $errors[] = 'duration_minutes must be a whole number of minutes.';
            } elseif ((int) $row['duration_minutes'] < 1 || (int) $row['duration_minutes'] > 1440) {
                $errors[] = 'duration_minutes must be between 1 and 1440.';
            }
        }

        return $errors;
    }

    private function isValidDate(string $value): bool
    {
        try {
            Carbon::parse($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
