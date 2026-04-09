<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Member;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MemberSeeder extends Seeder
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

        $members = [
            ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'sex' => 'male'],
            ['first_name' => 'Maria', 'last_name' => 'Santos', 'sex' => 'female'],
            ['first_name' => 'Paolo', 'last_name' => 'Reyes', 'sex' => 'male'],
            ['first_name' => 'Angela', 'last_name' => 'Garcia', 'sex' => 'female'],
            ['first_name' => 'Mark', 'last_name' => 'Bautista', 'sex' => 'male'],
            ['first_name' => 'Trisha', 'last_name' => 'Mendoza', 'sex' => 'female'],
            ['first_name' => 'Carlo', 'last_name' => 'Navarro', 'sex' => 'male'],
            ['first_name' => 'Bianca', 'last_name' => 'Lim', 'sex' => 'female'],
            ['first_name' => 'Kevin', 'last_name' => 'Torres', 'sex' => 'male'],
            ['first_name' => 'Jessa', 'last_name' => 'Castro', 'sex' => 'female'],
        ];

        foreach ($members as $index => $member) {
            $sequence = $index + 1;

            Member::updateOrCreate(
                ['member_code' => sprintf('MBR-%04d', $sequence)],
                [
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                    'first_name' => $member['first_name'],
                    'last_name' => $member['last_name'],
                    'email' => sprintf('member%02d@demofitness.local', $sequence),
                    'phone' => sprintf('+63 917 200 %04d', $sequence),
                    'birthdate' => Carbon::now()->subYears(20 + $sequence)->subDays($sequence),
                    'sex' => $member['sex'],
                    'address' => sprintf('%d Wellness Ave, Makati City', 100 + $sequence),
                    'emergency_contact_name' => sprintf('%s Emergency', $member['first_name']),
                    'emergency_contact_phone' => sprintf('+63 917 300 %04d', $sequence),
                    'qr_code_value' => sprintf('QR-MBR-%04d', $sequence),
                    'status' => $sequence <= 8 ? 'active' : 'inactive',
                    'joined_at' => Carbon::now()->subDays($sequence * 3),
                ],
            );
        }
    }
}