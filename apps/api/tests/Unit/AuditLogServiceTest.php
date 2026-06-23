<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\GymPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AuditLogService;
    }

    public function test_record_creates_audit_log_entry(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor, 'member' => $member] = $this->createFixture();

        $auditLog = $this->service->record(
            action: 'member.created',
            auditable: $member,
            actor: $actor,
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'actor_id' => $actor->id,
            'action' => 'member.created',
            'auditable_type' => Member::class,
            'auditable_id' => $member->id,
        ]);
    }

    public function test_state_diff_is_computed_correctly(): void
    {
        ['actor' => $actor, 'member' => $member] = $this->createFixture();

        $before = ['first_name' => 'Pat', 'last_name' => 'Lee', 'status' => 'active'];
        $after = ['first_name' => 'Patrick', 'last_name' => 'Lee', 'status' => 'inactive'];

        $auditLog = $this->service->record(
            action: 'member.updated',
            auditable: $member,
            actor: $actor,
            before: $before,
            after: $after,
        );

        $auditLog->refresh();

        $this->assertSame($before, $auditLog->before_state);
        $this->assertSame($after, $auditLog->after_state);
        $this->assertContains('first_name', $auditLog->changed_fields);
        $this->assertContains('status', $auditLog->changed_fields);
        $this->assertNotContains('last_name', $auditLog->changed_fields);
    }

    public function test_changed_fields_are_tracked(): void
    {
        ['actor' => $actor, 'member' => $member] = $this->createFixture();

        $before = ['status' => 'active', 'first_name' => 'Pat'];
        $after = ['status' => 'frozen', 'first_name' => 'Pat'];

        $auditLog = $this->service->record(
            action: 'member.updated',
            auditable: $member,
            actor: $actor,
            before: $before,
            after: $after,
        );

        $auditLog->refresh();

        $this->assertSame(['status'], $auditLog->changed_fields);
    }

    public function test_changed_fields_null_when_both_states_are_null(): void
    {
        ['actor' => $actor, 'member' => $member] = $this->createFixture();

        $auditLog = $this->service->record(
            action: 'member.viewed',
            auditable: $member,
            actor: $actor,
            before: null,
            after: null,
        );

        $auditLog->refresh();

        $this->assertNull($auditLog->changed_fields);
    }

    public function test_changed_fields_lists_all_keys_when_before_is_null(): void
    {
        ['actor' => $actor, 'member' => $member] = $this->createFixture();

        $after = ['first_name' => 'Pat', 'last_name' => 'Lee', 'status' => 'active'];

        $auditLog = $this->service->record(
            action: 'member.created',
            auditable: $member,
            actor: $actor,
            before: null,
            after: $after,
        );

        $auditLog->refresh();

        $this->assertSame(['first_name', 'last_name', 'status'], $auditLog->changed_fields);
    }

    public function test_metadata_is_stored(): void
    {
        ['actor' => $actor, 'member' => $member] = $this->createFixture();

        $auditLog = $this->service->record(
            action: 'member.created',
            auditable: $member,
            actor: $actor,
            metadata: ['source' => 'api', 'ip' => '127.0.0.1'],
        );

        $auditLog->refresh();

        $this->assertSame(['source' => 'api', 'ip' => '127.0.0.1'], $auditLog->metadata);
    }

    public function test_summary_is_stored(): void
    {
        ['actor' => $actor, 'member' => $member] = $this->createFixture();

        $auditLog = $this->service->record(
            action: 'member.created',
            auditable: $member,
            actor: $actor,
            summary: 'Created member Pat Lee',
        );

        $auditLog->refresh();

        $this->assertSame('Created member Pat Lee', $auditLog->summary);
    }

    private function createFixture(): array
    {
        Model::unguard();

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

        $member = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
            'first_name' => 'Pat',
            'last_name' => 'Lee',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Model::reguard();

        return compact('tenant', 'branch', 'actor', 'member');
    }
}
