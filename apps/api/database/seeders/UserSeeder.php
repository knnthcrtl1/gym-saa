<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-fitness-club'],
            [
                'name' => 'Demo Fitness Club',
                'email' => 'hello@demofitness.local',
                'phone' => '+63 917 100 0001',
                'address' => '123 Demo Street, Makati City',
                'status' => 'active',
            ],
        );

        $branch = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Demo Fitness Club - Main Branch'],
            [
                'code' => 'MAIN',
                'email' => 'mainbranch@demofitness.local',
                'phone' => '+63 917 100 0002',
                'address' => '123 Demo Street, Makati City',
                'status' => 'active',
            ],
        );

        User::updateOrCreate(
            ['email' => 'superadmin@gymsaas.local'],
            [
                'tenant_id' => null,
                'branch_id' => null,
                'name' => 'Gym SaaS Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'admin@demofitness.local'],
            [
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => 'Demo Gym Admin',
                'password' => Hash::make('password'),
                'role' => 'gym_admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );
    }
}