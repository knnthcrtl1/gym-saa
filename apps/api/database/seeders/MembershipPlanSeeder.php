<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\MembershipPlan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
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

        $plans = [
            [
                'name' => 'Monthly Unlimited',
                'description' => '30-day unlimited gym access for regular members.',
                'duration_type' => 'month',
                'duration_value' => 1,
                'price' => 1500,
                'session_limit' => null,
                'freeze_limit_days' => 7,
            ],
            [
                'name' => 'Annual Unlimited',
                'description' => '12-month discounted unlimited access plan.',
                'duration_type' => 'year',
                'duration_value' => 1,
                'price' => 15000,
                'session_limit' => null,
                'freeze_limit_days' => 30,
            ],
            [
                'name' => 'Day Pass',
                'description' => 'Single-day access for walk-in members.',
                'duration_type' => 'day',
                'duration_value' => 1,
                'price' => 250,
                'session_limit' => 1,
                'freeze_limit_days' => null,
            ],
        ];

        foreach ($plans as $plan) {
            MembershipPlan::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                    'name' => $plan['name'],
                ],
                [
                    'description' => $plan['description'],
                    'duration_type' => $plan['duration_type'],
                    'duration_value' => $plan['duration_value'],
                    'price' => $plan['price'],
                    'session_limit' => $plan['session_limit'],
                    'freeze_limit_days' => $plan['freeze_limit_days'],
                    'status' => 'active',
                ],
            );
        }
    }
}