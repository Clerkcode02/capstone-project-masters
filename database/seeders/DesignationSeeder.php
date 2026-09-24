<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Team Lead', 'utilization_target' => 0.400],
            ['name' => 'Sr. Reports Analyst', 'utilization_target' => 0.800],
            ['name' => 'Reports Analyst', 'utilization_target' => 0.800],
            ['name' => 'Toning Analyst', 'utilization_target' => 0.800],
            ['name' => 'Jr. Reports Analyst', 'utilization_target' => 0.800],
        ])->each(fn (array $designation) => Designation::query()->firstOrCreate(
            ['name' => $designation['name']],
            $designation
        ));
    }
}
