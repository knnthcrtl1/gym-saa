<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GymPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_members_returns_tenant_scoped_members(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_list_members_returns_empty_when_no_members(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_list_members_is_paginated(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        for ($i = 0; $i < 15; $i++) {
            Member::query()->create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'member_code' => 'MBR-'.uniqid(),
                'first_name' => "Member{$i}",
                'last_name' => 'Test',
                'status' => 'active',
                'joined_at' => now()->toDateString(),
            ]);
        }

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/members?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('last_page', 3);
    }

    public function test_create_member_with_valid_data(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $payload = [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-NEW-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.test',
            'phone' => '09171234567',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ];

        $this->postJson('/api/v1/members', $payload)
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.last_name', 'Doe')
            ->assertJsonPath('data.email', 'jane@example.test');

        $this->assertDatabaseHas('members', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-NEW-001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
    }

    public function test_create_member_validates_required_fields(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/members', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'tenant_id',
                'branch_id',
                'member_code',
                'first_name',
                'last_name',
                'status',
            ]);
    }

    public function test_create_member_validates_email_format(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/members', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'not-an-email',
            'status' => 'active',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_update_member(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        $member = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
            'first_name' => 'Original',
            'last_name' => 'Name',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actor);

        $this->putJson("/api/v1/members/{$member->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Member',
        ])->assertOk()
            ->assertJsonPath('data.first_name', 'Updated')
            ->assertJsonPath('data.last_name', 'Member');

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'first_name' => 'Updated',
            'last_name' => 'Member',
        ]);
    }

    public function test_cannot_update_other_tenants_member(): void
    {
        ['tenant' => $tenantA, 'branch' => $branchA, 'actor' => $actorA] = $this->createFixture();

        $otherTenant = Tenant::query()->create([
            'name' => 'Other Gym',
            'slug' => 'other-gym-'.uniqid(),
            'status' => 'active',
        ]);

        $otherBranch = Branch::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Branch',
            'status' => 'active',
        ]);

        $otherMember = Member::query()->create([
            'tenant_id' => $otherTenant->id,
            'branch_id' => $otherBranch->id,
            'member_code' => 'MBR-OTHER-'.uniqid(),
            'first_name' => 'Other',
            'last_name' => 'Member',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actorA);

        $this->putJson("/api/v1/members/{$otherMember->id}", [
            'first_name' => 'Hacked',
        ])->assertNotFound();
    }

    public function test_delete_member(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        $member = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
            'first_name' => 'ToDelete',
            'last_name' => 'Member',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actor);

        $this->deleteJson("/api/v1/members/{$member->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Member deleted successfully');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_cannot_delete_other_tenants_member(): void
    {
        ['actor' => $actorA] = $this->createFixture();

        $otherTenant = Tenant::query()->create([
            'name' => 'Other Gym',
            'slug' => 'other-gym-'.uniqid(),
            'status' => 'active',
        ]);

        $otherBranch = Branch::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Branch',
            'status' => 'active',
        ]);

        $otherMember = Member::query()->create([
            'tenant_id' => $otherTenant->id,
            'branch_id' => $otherBranch->id,
            'member_code' => 'MBR-OTHER-'.uniqid(),
            'first_name' => 'Other',
            'last_name' => 'Member',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actorA);

        $this->deleteJson("/api/v1/members/{$otherMember->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('members', ['id' => $otherMember->id]);
    }

    public function test_bulk_delete_members(): void
    {
        ['tenant' => $tenant, 'branch' => $branch, 'actor' => $actor] = $this->createFixture();

        $member1 = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-BULK-'.uniqid(),
            'first_name' => 'Bulk1',
            'last_name' => 'Delete',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        $member2 = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-BULK-'.uniqid(),
            'first_name' => 'Bulk2',
            'last_name' => 'Delete',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        Sanctum::actingAs($actor);

        $this->deleteJson('/api/v1/members/bulk-delete', [
            'ids' => [$member1->id, $member2->id],
        ])->assertOk()
            ->assertJsonPath('deleted', 2);

        $this->assertDatabaseMissing('members', ['id' => $member1->id]);
        $this->assertDatabaseMissing('members', ['id' => $member2->id]);
    }

    public function test_bulk_delete_validates_ids_exist(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->deleteJson('/api/v1/members/bulk-delete', [
            'ids' => [99999],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('ids.0');
    }

    private function createFixture(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Member Test Gym',
            'slug' => 'member-test-gym-'.uniqid(),
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
            'password' => 'password123',
            'role' => 'gym_admin',
            'staff_role' => 'owner',
            'status' => 'active',
            'permissions' => GymPermission::defaultFor('gym_admin'),
        ]);

        return compact('tenant', 'branch', 'actor');
    }
}
