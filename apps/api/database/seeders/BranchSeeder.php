<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
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

        Branch::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Demo Fitness Club - Main Branch',
            ],
            [
                'code' => 'MAIN',
                'email' => 'mainbranch@demofitness.local',
                'phone' => '+63 917 100 0002',
                'address' => '123 Demo Street, Makati City',
                'status' => 'active',
            ],
        );
    }
}