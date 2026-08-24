<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\DTOs\ProductionSheetRowResult;
use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Throwable;

class ProductionSheetRowValidator
{
    /** @var array<string, int> employee_code => user_id */
    private array $userIds;

    /** @var array<string, int> account code => account_id */
    private array $accountIds;

    /** @var array<string, true> keys already seen earlier in this batch */
    private array $seenInBatch = [];

    public function __construct()
    {
        $this->userIds = User::query()
            ->whereNotNull('employee_code')
            ->where('is_active', true)
            ->pluck('id', 'employee_code')
            ->all();

        $this->accountIds = Account::query()
            ->where('is_active', true)
            ->pluck('id', 'code')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public function validate(int $rowNumber, array $raw): ProductionSheetRowResult
    {
        $reasons = [];

        $employeeCode = trim((string) ($raw['employee_code'] ?? ''));
        $accountCode = trim((string) ($raw['account_code'] ?? ''));
        $hourType = trim((string) ($raw['hour_type'] ?? ''));
        $logDateRaw = trim((string) ($raw['log_date'] ?? ''));
        $hoursRaw = $raw['hours'] ?? null;
        $notes = trim((string) ($raw['notes'] ?? ''));

        $userId = $this->resolveUserId($employeeCode, $reasons);
        $accountId = $this->resolveAccountId($accountCode, $reasons);
        $hourTypeEnum = $this->resolveHourType($hourType, $reasons);
        $logDate = $this->resolveLogDate($logDateRaw, $reasons);
        $durationMinutes = $this->resolveDurationMinutes($hoursRaw, $reasons);

        if ($userId !== null && $accountId !== null && $hourTypeEnum !== null && $logDate !== null) {
            $this->guardAgainstDuplicates($userId, $accountId, $hourTypeEnum, $logDate, $reasons);
        }

        if ($reasons !== []) {
            return new ProductionSheetRowResult($rowNumber, $raw, false, $reasons);
        }

        return new ProductionSheetRowResult($rowNumber, $raw, true, [], [
            'user_id' => $userId,
            'task_id' => null,
            'account_id' => $accountId,
            'log_date' => $logDate->toDateString(),
            'hour_type' => $hourTypeEnum->value,
            'duration_minutes' => $durationMinutes,
            'entry_method' => EntryMethod::Import->value,
            'notes' => $notes !== '' ? $notes : null,
        ]);
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function resolveUserId(string $employeeCode, array &$reasons): ?int
    {
        if ($employeeCode === '') {
            $reasons[] = 'employee_code is required.';

            return null;
        }

        if (! isset($this->userIds[$employeeCode])) {
            $reasons[] = "employee_code '{$employeeCode}' does not match an active employee.";

            return null;
        }

        return $this->userIds[$employeeCode];
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function resolveAccountId(string $accountCode, array &$reasons): ?int
    {
        if ($accountCode === '') {
            $reasons[] = 'account_code is required.';

            return null;
        }

        if (! isset($this->accountIds[$accountCode])) {
            $reasons[] = "account_code '{$accountCode}' does not match an active account.";

            return null;
        }

        return $this->accountIds[$accountCode];
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function resolveHourType(string $hourType, array &$reasons): ?HourType
    {
        if ($hourType === '') {
            $reasons[] = 'hour_type is required.';

            return null;
        }

        $enum = HourType::tryFrom($hourType);

        if ($enum === null) {
            $values = implode(', ', array_map(fn (HourType $t) => $t->value, HourType::cases()));
            $reasons[] = "hour_type must be one of: {$values}.";

            return null;
        }

        return $enum;
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function resolveLogDate(string $logDateRaw, array &$reasons): ?Carbon
    {
        if ($logDateRaw === '') {
            $reasons[] = 'log_date is required.';

            return null;
        }

        try {
            $logDate = Carbon::parse($logDateRaw)->startOfDay();
        } catch (Throwable) {
            $reasons[] = "log_date '{$logDateRaw}' is not a valid date.";

            return null;
        }

        if ($logDate->isFuture()) {
            $reasons[] = 'log_date cannot be in the future.';

            return null;
        }

        return $logDate;
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function resolveDurationMinutes(mixed $hoursRaw, array &$reasons): ?int
    {
        if ($hoursRaw === null || $hoursRaw === '') {
            $reasons[] = 'hours is required.';

            return null;
        }

        if (! is_numeric($hoursRaw)) {
            $reasons[] = 'hours must be numeric.';

            return null;
        }

        $hours = (float) $hoursRaw;

        if ($hours <= 0) {
            $reasons[] = 'hours must be greater than 0.';

            return null;
        }

        if ($hours > 24) {
            $reasons[] = 'hours cannot exceed 24.';

            return null;
        }

        return (int) round($hours * 60);
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function guardAgainstDuplicates(int $userId, int $accountId, HourType $hourType, Carbon $logDate, array &$reasons): void
    {
        $key = implode('|', [$userId, $logDate->toDateString(), $accountId, $hourType->value]);

        if (isset($this->seenInBatch[$key])) {
            $reasons[] = 'duplicate row: another row in this file already has the same employee, date, account, and hour type.';

            return;
        }

        $existing = TimeLog::query()
            ->where('user_id', $userId)
            ->whereDate('log_date', $logDate->toDateString())
            ->where('account_id', $accountId)
            ->where('hour_type', $hourType->value)
            ->exists();

        if ($existing) {
            $reasons[] = 'a time log already exists for this employee, date, account, and hour type.';

            return;
        }

        $this->seenInBatch[$key] = true;
    }
}
