<?php

namespace Database\Seeders;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\Services\CapacityCalculationService;
use App\Domain\Optimization\Services\BottleneckDetectionService;
use App\Domain\Optimization\Services\RedistributionRecommender;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\Designation;
use App\Models\MonthlyBaseline;
use App\Models\Role;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Populates three complete historical months plus the current (in-progress)
 * month so the demo is never an empty dashboard. Deterministic: no Faker,
 * no mt_rand — every name, date, and hour figure is fixed so the dataset is
 * identical on every `migrate:fresh --seed`.
 *
 * See .claude/skills/capstone-artifacts/SKILL.md "Alpha UAT dataset" for the
 * exact coverage this seeder is required to hit.
 */
class HistoricalDatasetSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /** Fixed per-tier "typical" hours used to size individual tasks. */
    private const TIER_HOURS = [
        'small' => 3.0,
        'medium' => 8.0,
        'large' => 18.0,
    ];

    /** Deterministic variance cycle applied to completed historical tasks. */
    private const VARIANCE_CYCLE = [0.90, 1.00, 1.05, 0.95, 1.10, 1.00, 0.85];

    private int $taskSequence = 1;

    /** @var array<string, float> keyed "tier|account_id" */
    private array $historicalAverages = [];

    /** @var array<string, array<int, Task>> keyed "periodOffset|userId" */
    private array $tasksByPeriodAndUser = [];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly CapacityCalculationService $capacityCalculationService,
        private readonly BottleneckDetectionService $bottleneckDetectionService,
        private readonly RedistributionRecommender $redistributionRecommender,
    ) {}

    public function run(): void
    {
        $roles = Role::query()->get()->keyBy('name');
        $designations = Designation::query()->get()->keyBy('name');
        $accounts = Account::query()->get()->keyBy('code');

        $this->createAdmin($roles);
        $staff = $this->createStaff($roles, $designations);
        $this->assignAccounts($staff, $accounts);

        $periods = $this->periods();

        foreach ($periods as $period) {
            if (! $period['is_current']) {
                $this->seedTasksForMonth($period, $staff, $accounts, completed: true);
            }
        }

        $this->computeHistoricalAverages();

        $currentPeriod = collect($periods)->firstWhere('is_current', true);
        $this->seedTasksForMonth($currentPeriod, $staff, $accounts, completed: false);
        $this->seedBottleneckCandidates($currentPeriod, $staff, $accounts);

        foreach ($periods as $period) {
            $this->seedTimeLogsForMonth($period, $staff);
        }

        foreach ($periods as $period) {
            $this->capacityCalculationService->recalculateForMonth($period['year'], $period['month']);
        }

        $this->seedPendingRecommendation();
    }

    /**
     * Runs the real bottleneck sweep against the freshly seeded data so a
     * pending recommendation already exists the moment the demo starts —
     * an empty recommendation queue at defense time is a failure.
     */
    private function seedPendingRecommendation(): void
    {
        foreach ($this->bottleneckDetectionService->detect() as $entry) {
            $this->redistributionRecommender->recommend(
                $entry['task'],
                $entry['actual_hours'],
                $entry['historical_avg'],
                $entry['variance_percentage'],
                $entry['basis'],
            );
        }
    }

    /**
     * @return array<int, array{offset: int, year: int, month: int, start: Carbon, end: Carbon, is_current: bool}>
     */
    private function periods(): array
    {
        $currentStart = Carbon::now()->startOfMonth();
        $periods = [];

        for ($offset = 3; $offset >= 0; $offset--) {
            $start = $currentStart->copy()->subMonths($offset);
            $isCurrent = $offset === 0;
            $end = $isCurrent ? Carbon::now()->copy()->startOfDay() : $start->copy()->endOfMonth();

            $periods[] = [
                'offset' => $offset,
                'year' => $start->year,
                'month' => $start->month,
                'start' => $start->copy()->startOfDay(),
                'end' => $end,
                'is_current' => $isCurrent,
            ];
        }

        return $periods;
    }

    private function createAdmin(Collection $roles): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'andrea.reyes@mediainsights.demo'],
            [
                'employee_code' => 'EMP-0001',
                'first_name' => 'Andrea',
                'last_name' => 'Reyes',
                'password' => Hash::make(self::PASSWORD),
                'role_id' => $roles['administrator']->id,
                'designation_id' => null,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * @return array<int, array{user: User, tier: string, account_code: string, full_leave_offset: int|null, partial_leave_offset: int|null}>
     */
    private function createStaff(Collection $roles, Collection $designations): array
    {
        $teamLeadDefs = [
            ['code' => 2, 'first' => 'Maria', 'last' => 'Santos', 'account_code' => 'SPR'],
            ['code' => 3, 'first' => 'Ramon', 'last' => 'Cruz', 'account_code' => 'MBC'],
        ];

        $teamLeads = [];

        foreach ($teamLeadDefs as $def) {
            $user = User::query()->updateOrCreate(
                ['email' => strtolower("{$def['first']}.{$def['last']}@mediainsights.demo")],
                [
                    'employee_code' => sprintf('EMP-%04d', $def['code']),
                    'first_name' => $def['first'],
                    'last_name' => $def['last'],
                    'password' => Hash::make(self::PASSWORD),
                    'role_id' => $roles['manager']->id,
                    'designation_id' => $designations['Team Lead']->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $teamLeads[] = [
                'user' => $user,
                'tier' => 'acceptable',
                'account_code' => $def['account_code'],
                'full_leave_offset' => null,
                'partial_leave_offset' => null,
            ];
        }

        $analystDefs = [
            ['code' => 4, 'first' => 'Katrina', 'last' => 'Villanueva', 'designation' => 'Sr. Reports Analyst', 'manager' => 0, 'account' => 'SPR', 'tier' => 'over'],
            ['code' => 5, 'first' => 'Miguel', 'last' => 'Torres', 'designation' => 'Sr. Reports Analyst', 'manager' => 0, 'account' => 'HMG', 'tier' => 'over'],
            ['code' => 6, 'first' => 'Angelica', 'last' => 'Bautista', 'designation' => 'Sr. Reports Analyst', 'manager' => 0, 'account' => 'MBC', 'tier' => 'acceptable'],
            ['code' => 7, 'first' => 'Paolo', 'last' => 'Mendoza', 'designation' => 'Reports Analyst', 'manager' => 0, 'account' => 'SPR', 'tier' => 'acceptable'],
            ['code' => 8, 'first' => 'Christine', 'last' => 'Aquino', 'designation' => 'Reports Analyst', 'manager' => 1, 'account' => 'HMG', 'tier' => 'acceptable'],
            ['code' => 9, 'first' => 'Joshua', 'last' => 'Ramos', 'designation' => 'Reports Analyst', 'manager' => 1, 'account' => 'MBC', 'tier' => 'acceptable'],
            ['code' => 10, 'first' => 'Bianca', 'last' => 'Garcia', 'designation' => 'Toning Analyst', 'manager' => 1, 'account' => 'SPR', 'tier' => 'acceptable', 'partial_leave_offset' => 2],
            ['code' => 11, 'first' => 'Nathaniel', 'last' => 'Flores', 'designation' => 'Toning Analyst', 'manager' => 1, 'account' => 'HMG', 'tier' => 'acceptable'],
            ['code' => 12, 'first' => 'Samantha', 'last' => 'Dela Cruz', 'designation' => 'Toning Analyst', 'manager' => 0, 'account' => 'MBC', 'tier' => 'acceptable'],
            ['code' => 13, 'first' => 'Kevin', 'last' => 'Domingo', 'designation' => 'Jr. Reports Analyst', 'manager' => 0, 'account' => 'SPR', 'tier' => 'below'],
            ['code' => 14, 'first' => 'Faith', 'last' => 'Navarro', 'designation' => 'Jr. Reports Analyst', 'manager' => 1, 'account' => 'HMG', 'tier' => 'acceptable', 'full_leave_offset' => 1],
            ['code' => 15, 'first' => 'Renz', 'last' => 'Aguilar', 'designation' => 'Jr. Reports Analyst', 'manager' => 1, 'account' => 'MBC', 'tier' => 'below'],
        ];

        $analysts = [];

        foreach ($analystDefs as $def) {
            $user = User::query()->updateOrCreate(
                ['email' => strtolower(str_replace(' ', '', "{$def['first']}.{$def['last']}@mediainsights.demo"))],
                [
                    'employee_code' => sprintf('EMP-%04d', $def['code']),
                    'first_name' => $def['first'],
                    'last_name' => $def['last'],
                    'password' => Hash::make(self::PASSWORD),
                    'role_id' => $roles['employee']->id,
                    'designation_id' => $designations[$def['designation']]->id,
                    'manager_id' => $teamLeads[$def['manager']]['user']->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $analysts[] = [
                'user' => $user,
                'tier' => $def['tier'],
                'account_code' => $def['account'],
                'full_leave_offset' => $def['full_leave_offset'] ?? null,
                'partial_leave_offset' => $def['partial_leave_offset'] ?? null,
            ];
        }

        return array_merge($teamLeads, $analysts);
    }

    /**
     * @param  array<int, array{user: User, account_code: string}>  $staff
     */
    private function assignAccounts(array $staff, Collection $accounts): void
    {
        foreach ($staff as $member) {
            $account = $accounts[$member['account_code']];

            $account->users()->syncWithoutDetaching([
                $member['user']->id => ['assigned_at' => now()->subMonths(4)],
            ]);
        }
    }

    /**
     * @param  array<int, array{user: User, tier: string, account_code: string, full_leave_offset: int|null, partial_leave_offset: int|null}>  $staff
     */
    private function seedTasksForMonth(array $period, array $staff, Collection $accounts, bool $completed): void
    {
        $weightSmall = $this->settings->int('complexity_weight_small', 1);
        $weightMedium = $this->settings->int('complexity_weight_medium', 3);
        $weightLarge = $this->settings->int('complexity_weight_large', 5);

        $tierMix = [
            ComplexityTier::Small->value => ['count' => 9, 'weight' => $weightSmall],
            ComplexityTier::Medium->value => ['count' => 7, 'weight' => $weightMedium],
            ComplexityTier::Large->value => ['count' => 4, 'weight' => $weightLarge],
        ];

        $titles = $this->taskTitlePool();

        foreach ($accounts as $account) {
            $roster = collect($staff)->filter(function (array $member) use ($account, $period) {
                return $member['account_code'] === $account->code
                    && $member['full_leave_offset'] !== $period['offset'];
            })->values();

            if ($roster->isEmpty()) {
                continue;
            }

            $rosterIndex = 0;
            $varianceIndex = 0;

            foreach ($tierMix as $tierValue => $mix) {
                $tier = ComplexityTier::from($tierValue);
                $standardHours = self::TIER_HOURS[$tierValue];

                for ($i = 0; $i < $mix['count']; $i++) {
                    $assignee = $roster[$rosterIndex % $roster->count()]['user'];
                    $rosterIndex++;

                    $status = $completed
                        ? TaskStatus::Completed
                        : $this->currentMonthStatus($i);

                    $variance = self::VARIANCE_CYCLE[$varianceIndex % count(self::VARIANCE_CYCLE)];
                    $varianceIndex++;

                    $actualHours = $status === TaskStatus::Pending
                        ? 0.0
                        : round($standardHours * $variance, 2);

                    $dueDate = $period['start']->copy()->addDays(min(27, ($i + 1) * 3));
                    $startedAt = $status === TaskStatus::Pending ? null : $period['start']->copy()->addDays($i % 20);
                    $completedAt = $status === TaskStatus::Completed ? $startedAt->copy()->addDays(2) : null;

                    $task = Task::query()->create([
                        'reference' => $this->nextTaskReference(),
                        'title' => $titles[$tierValue][$i % count($titles[$tierValue])],
                        'description' => null,
                        'account_id' => $account->id,
                        'assigned_to' => $assignee->id,
                        'created_by' => $assignee->manager_id ?? $assignee->id,
                        'complexity_tier' => $tier->value,
                        'complexity_weight' => $mix['weight'],
                        'standard_hours' => $standardHours,
                        'actual_hours' => $actualHours,
                        'status' => $status->value,
                        'due_date' => $dueDate->toDateString(),
                        'started_at' => $startedAt,
                        'completed_at' => $completedAt,
                    ]);

                    $this->recordTaskForPeriod($period, $task);
                }
            }
        }
    }

    private function recordTaskForPeriod(array $period, Task $task): void
    {
        $key = "{$period['offset']}|{$task->assigned_to}";
        $this->tasksByPeriodAndUser[$key][] = $task;
    }

    private function currentMonthStatus(int $index): TaskStatus
    {
        return match ($index % 5) {
            0, 1 => TaskStatus::Completed,
            2, 3 => TaskStatus::InProgress,
            default => TaskStatus::Pending,
        };
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function taskTitlePool(): array
    {
        return [
            'small' => [
                'Daily media monitoring digest',
                'Social media sentiment snapshot',
                'Press clipping compilation',
                'Competitor headline scan',
                'Daily toning spreadsheet update',
            ],
            'medium' => [
                'Weekly toning report',
                'Client sentiment analysis report',
                'Competitor coverage summary',
                'Share-of-voice weekly update',
            ],
            'large' => [
                'Monthly executive PR performance report',
                'Crisis communication audit',
                'Quarterly brand reputation deep-dive',
            ],
        ];
    }

    private function nextTaskReference(): string
    {
        return sprintf('TSK-%d-%04d', Carbon::now()->year, $this->taskSequence++);
    }

    private function computeHistoricalAverages(): void
    {
        $rows = Task::query()
            ->where('status', TaskStatus::Completed->value)
            ->where('actual_hours', '>', 0)
            ->selectRaw('complexity_tier, account_id, AVG(actual_hours) as avg_hours')
            ->groupBy('complexity_tier', 'account_id')
            ->get();

        foreach ($rows as $row) {
            $tier = $row->complexity_tier instanceof ComplexityTier ? $row->complexity_tier->value : $row->complexity_tier;
            $this->historicalAverages["{$tier}|{$row->account_id}"] = round((float) $row->avg_hours, 2);
        }
    }

    /**
     * @param  array<int, array{user: User, tier: string, account_code: string}>  $staff
     */
    private function seedBottleneckCandidates(array $period, array $staff, Collection $accounts): void
    {
        $variancePct = $this->settings->decimal('bottleneck_variance_pct', 25);
        $weightMedium = $this->settings->int('complexity_weight_medium', 3);

        $candidates = [
            ['account_code' => 'HMG', 'assignee_email' => 'christine.aquino@mediainsights.demo'],
            ['account_code' => 'MBC', 'assignee_email' => 'nathaniel.flores@mediainsights.demo'],
        ];

        foreach ($candidates as $candidate) {
            $account = $accounts[$candidate['account_code']];
            $assignee = collect($staff)->firstWhere('user.email', $candidate['assignee_email'])['user'];

            $historicalAvg = $this->historicalAverages['medium|'.$account->id] ?? self::TIER_HOURS['medium'];
            $overrunHours = round($historicalAvg * (1 + ($variancePct + 35) / 100), 2);

            $task = Task::query()->create([
                'reference' => $this->nextTaskReference(),
                'title' => 'Weekly toning report — overrun (bottleneck candidate)',
                'description' => null,
                'account_id' => $account->id,
                'assigned_to' => $assignee->id,
                'created_by' => $assignee->manager_id ?? $assignee->id,
                'complexity_tier' => ComplexityTier::Medium->value,
                'complexity_weight' => $weightMedium,
                'standard_hours' => self::TIER_HOURS['medium'],
                'actual_hours' => $overrunHours,
                'status' => TaskStatus::InProgress->value,
                'due_date' => $period['end']->copy()->addDays(3)->toDateString(),
                'started_at' => $period['start']->copy()->addDays(2),
            ]);

            $this->recordTaskForPeriod($period, $task);
        }
    }

    /**
     * @param  array<int, array{user: User, tier: string, account_code: string, full_leave_offset: int|null, partial_leave_offset: int|null}>  $staff
     */
    private function seedTimeLogsForMonth(array $period, array $staff): void
    {
        $entryMethods = $period['is_current']
            ? [EntryMethod::Timer, EntryMethod::Manual]
            : [EntryMethod::Import, EntryMethod::Manual];

        foreach ($staff as $member) {
            $user = $member['user'];
            $accountId = $this->accountIdFor($member);

            $baseline = MonthlyBaseline::query()
                ->where('period_year', $period['year'])
                ->where('period_month', $period['month'])
                ->firstOrFail();

            $hBase = (float) $baseline->baseline_hours;
            $uTarget = (float) $user->designation->utilization_target;

            $isFullLeave = $member['full_leave_offset'] === $period['offset'];
            $isPartialLeave = $member['partial_leave_offset'] === $period['offset'];

            $hLeave = match (true) {
                $isFullLeave => $hBase,
                $isPartialLeave => 16.0,
                default => 0.0,
            };

            if ($hLeave > 0) {
                $this->createTimeLog($user, null, $accountId, $period['start']->copy()->addDays(4), HourType::Leave, $hLeave, EntryMethod::Manual);
            }

            if ($isFullLeave) {
                continue;
            }

            $hPoss = round($hBase - $hLeave, 2);
            $hThresh = round($hPoss * $uTarget, 2);

            $targetPct = match ($member['tier']) {
                'below' => 0.75,
                'over' => 1.25,
                default => 1.00,
            };

            $targetHours = round($hThresh * $targetPct, 2);

            $periodTasks = collect($this->tasksByPeriodAndUser["{$period['offset']}|{$user->id}"] ?? [])
                ->filter(fn (Task $task) => in_array($task->status->value, [TaskStatus::Completed->value, TaskStatus::InProgress->value], true));

            $tasksHours = round((float) $periodTasks->sum(fn (Task $task) => (float) $task->actual_hours), 2);

            foreach ($periodTasks->values() as $index => $task) {
                $method = $entryMethods[$index % count($entryMethods)];
                $logDate = $task->started_at ?? $period['start']->copy()->addDays(3);
                $this->createTimeLog($user, $task->id, $task->account_id, $logDate, HourType::Production, (float) $task->actual_hours, $method);
            }

            $topUp = round($targetHours - $tasksHours, 2);

            if ($topUp > 0) {
                $rows = 5;
                $perRow = round($topUp / $rows, 2);
                $daySpan = max(1, (int) $period['start']->diffInDays($period['end']));

                for ($i = 0; $i < $rows; $i++) {
                    $hours = $i === $rows - 1 ? round($topUp - $perRow * ($rows - 1), 2) : $perRow;

                    if ($hours <= 0) {
                        continue;
                    }

                    $dayOffset = (int) round(($daySpan / $rows) * $i);
                    $logDate = $period['start']->copy()->addDays(min($dayOffset, $daySpan));
                    $method = $entryMethods[$i % count($entryMethods)];

                    $this->createTimeLog($user, null, $accountId, $logDate, HourType::Production, $hours, $method);
                }
            }

            $nonProdDate = $period['start']->copy()->addDays(1);
            $this->createTimeLog($user, null, $accountId, $nonProdDate, HourType::NonProduction, 4.0, EntryMethod::Manual);
        }
    }

    private function accountIdFor(array $member): int
    {
        static $cache = [];

        $code = $member['account_code'];

        if (! isset($cache[$code])) {
            $cache[$code] = Account::query()->where('code', $code)->value('id');
        }

        return $cache[$code];
    }

    private function createTimeLog(User $user, ?int $taskId, ?int $accountId, Carbon $date, HourType $hourType, float $hours, EntryMethod $method): void
    {
        if ($hours <= 0) {
            return;
        }

        TimeLog::query()->create([
            'user_id' => $user->id,
            'task_id' => $taskId,
            'account_id' => $accountId,
            'log_date' => $date->toDateString(),
            'hour_type' => $hourType->value,
            'duration_minutes' => (int) round($hours * 60),
            'entry_method' => $method->value,
        ]);
    }
}
