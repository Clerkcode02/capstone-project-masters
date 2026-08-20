<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'administrator', 'label' => 'Administrator', 'description' => 'Full system access.'],
            ['name' => 'manager', 'label' => 'Manager', 'description' => 'Team and account oversight.'],
            ['name' => 'employee', 'label' => 'Employee', 'description' => 'Individual contributor.'],
        ])->each(fn (array $role) => Role::query()->firstOrCreate(['name' => $role['name']], $role));
    }
}
