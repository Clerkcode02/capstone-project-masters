<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Solstice Public Relations', 'code' => 'SPR', 'expected_monthly_hours' => 480.00],
            ['name' => 'Harborlight Media Group', 'code' => 'HMG', 'expected_monthly_hours' => 420.00],
            ['name' => 'Meridian Brand Communications', 'code' => 'MBC', 'expected_monthly_hours' => 360.00],
        ])->each(fn (array $account) => Account::query()->firstOrCreate(
            ['code' => $account['code']],
            $account + ['is_active' => true]
        ));
    }
}
