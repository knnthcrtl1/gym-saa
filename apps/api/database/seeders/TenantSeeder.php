<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::updateOrCreate(
            ['slug' => 'demo-fitness-club'],
            [
                'name' => 'Demo Fitness Club',
                'email' => 'hello@demofitness.local',
                'phone' => '+63 917 100 0001',
                'address' => '123 Demo Street, Makati City',
                'status' => 'active',
            ],
        );
    }
}