<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GymPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_endpoint_returns_paginated_results(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        Sanctum::actingAs($actor);

        AuditLog::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'actor_id' => $actor->id,
        ]);

        $this->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'action', 'auditable_type', 'auditable_id', 'created_at']],
                'current_page',
                'total',
            ]);
    }

    public function test_audit_logs_are_tenant_scoped(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        $otherTenant = Tenant::query()->create([
            'name' => 'Other Gym',
            'slug' => 'other-gym-'.uniqid(),
            'status' => 'active',
        ]);

        AuditLog::factory()->count(2)->create(['tenant_id' => $tenant->id, 'actor_id' => $actor->id]);
        AuditLog::factory()->count(3)->create(['tenant_id' => $otherTenant->id]);

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_audit_logs_filterable_by_action(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        Sanctum::actingAs($actor);

        AuditLog::factory()->create(['tenant_id' => $tenant->id, 'action' => 'payment.created', 'actor_id' => $actor->id]);
        AuditLog::factory()->create(['tenant_id' => $tenant->id, 'action' => 'subscription.created', 'actor_id' => $actor->id]);

        $this->getJson('/api/v1/audit-logs?action=payment.created')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'payment.created');
    }

    public function test_audit_logs_filterable_by_auditable_type(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        Sanctum::actingAs($actor);

        AuditLog::factory()->create([
            'tenant_id' => $tenant->id,
            'auditable_type' => Subscription::class,
            'actor_id' => $actor->id,
        ]);

        AuditLog::factory()->create([
            'tenant_id' => $tenant->id,
            'auditable_type' => Member::class,
            'actor_id' => $actor->id,
        ]);

        $this->getJson('/api/v1/audit-logs?auditable_type='.urlencode(Subscription::class))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_non_permitted_user_gets_403(): void
    {
        ['tenant' => $tenant, 'branch' => $branch] = $this->createFixture();

        $staff = User::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Trainer',
            'email' => 'trainer+'.uniqid().'@example.test',
            'password' => 'password',
            'role' => 'staff',
            'staff_role' => 'trainer',
            'status' => 'active',
            'permissions' => GymPermission::defaultForStaffRole('trainer'),
        ]);

        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/audit-logs')
            ->assertForbidden();
    }

    private function createFixture(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Acme Fitness',
            'slug' => 'acme-fitness-'.uniqid(),
            'status' => 'active',
        ]);

        $branch = Branch::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Branch',
            'status' => 'active',
        ]);

        $actor = User::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Gym Admin',
            'email' => 'admin+'.uniqid().'@example.test',
            'password' => 'password',
            'role' => 'gym_admin',
            'staff_role' => 'owner',
            'status' => 'active',
            'permissions' => GymPermission::defaultFor('gym_admin'),
        ]);

        return compact('tenant', 'branch', 'actor');
    }
}
