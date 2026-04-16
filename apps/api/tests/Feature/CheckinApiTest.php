<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Checkin;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GymPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckinApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkin_is_idempotent_with_same_key(): void
    {
        ['actor' => $actor, 'payload' => $payload] = $this->createCheckinFixture();

        Sanctum::actingAs($actor);

        $headers = ['X-Idempotency-Key' => 'checkin-001'];

        $first = $this->postJson('/api/v1/checkins', $payload, $headers)
            ->assertCreated();

        $second = $this->postJson('/api/v1/checkins', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Check-in already recorded for this idempotency key.');

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id'),
        );

        $this->assertDatabaseCount('checkins', 1);
    }

    public function test_checkin_creates_audit_log(): void
    {
        ['actor' => $actor, 'payload' => $payload] = $this->createCheckinFixture();

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/checkins', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'checkin.created',
            'auditable_type' => Checkin::class,
            'actor_id' => $actor->id,
        ]);
    }

    public function test_checkin_auto_activates_pending_subscription_with_audit(): void
    {
        ['actor' => $actor, 'payload' => $payload, 'subscription' => $subscription] = $this->createCheckinFixture('pending');

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/checkins', $payload)
            ->assertCreated();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'subscription.activated',
            'auditable_type' => Subscription::class,
            'auditable_id' => $subscription->id,
        ]);
    }

    private function createCheckinFixture(string $subscriptionStatus = 'active'): array
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
            'name' => 'Front Desk',
            'email' => 'desk+'.uniqid().'@example.test',
            'password' => 'password',
            'role' => 'staff',
            'staff_role' => 'front_desk',
            'status' => 'active',
            'permissions' => GymPermission::defaultForStaffRole('front_desk'),
        ]);

        $member = Member::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_code' => 'MBR-'.uniqid(),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
            'joined_at' => now()->toDateString(),
        ]);

        $plan = MembershipPlan::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Monthly Unlimited',
            'duration_type' => 'month',
            'duration_value' => 1,
            'price' => 1500,
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'amount' => 1500,
            'payment_status' => 'paid',
            'status' => $subscriptionStatus,
        ]);

        $payload = [
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'source' => 'manual',
        ];

        return compact('tenant', 'branch', 'actor', 'member', 'plan', 'subscription', 'payload');
    }
}
