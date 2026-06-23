<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\IdempotencyKey;
use App\Models\Tenant;
use App\Models\User;
use App\Services\IdempotencyService;
use App\Support\GymPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class IdempotencyServiceTest extends TestCase
{
    use RefreshDatabase;

    private IdempotencyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new IdempotencyService;
    }

    public function test_claim_creates_new_key(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        $request = $this->makeRequest($actor, 'idem-001');

        $result = $this->service->claim($request, 'subscriptions', [
            'tenant_id' => $tenant->id,
            'amount' => 1500,
        ]);

        $this->assertFalse($result['replayed']);
        $this->assertInstanceOf(IdempotencyKey::class, $result['record']);
        $this->assertSame('processing', $result['record']->status);
        $this->assertSame('idem-001', $result['record']->idempotency_key);
        $this->assertDatabaseHas('idempotency_keys', [
            'idempotency_key' => 'idem-001',
            'scope' => 'subscriptions',
            'status' => 'processing',
        ]);
    }

    public function test_claim_with_same_key_returns_existing_completed_resource(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        $payload = ['tenant_id' => $tenant->id, 'amount' => 1500];
        $request = $this->makeRequest($actor, 'idem-002');

        // First claim
        $first = $this->service->claim($request, 'subscriptions', $payload);
        $this->service->complete($first['record'], 201, 'subscription', 42);

        // Second claim with same key and payload
        $second = $this->service->claim($request, 'subscriptions', $payload);

        $this->assertTrue($second['replayed']);
        $this->assertSame($first['record']->id, $second['record']->id);
    }

    public function test_claim_with_same_key_but_different_payload_throws_conflict(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        $request = $this->makeRequest($actor, 'idem-003');

        $first = $this->service->claim($request, 'subscriptions', [
            'tenant_id' => $tenant->id,
            'amount' => 1500,
        ]);
        $this->service->complete($first['record'], 201, 'subscription', 42);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('This idempotency key has already been used for a different request payload.');

        $this->service->claim($request, 'subscriptions', [
            'tenant_id' => $tenant->id,
            'amount' => 2000,
        ]);
    }

    public function test_complete_marks_key_as_completed(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        $request = $this->makeRequest($actor, 'idem-004');

        $result = $this->service->claim($request, 'subscriptions', [
            'tenant_id' => $tenant->id,
            'amount' => 1500,
        ]);

        $this->service->complete($result['record'], 201, 'subscription', 99);

        $result['record']->refresh();

        $this->assertSame('completed', $result['record']->status);
        $this->assertSame(201, $result['record']->response_code);
        $this->assertSame('subscription', $result['record']->resource_type);
        $this->assertSame(99, $result['record']->resource_id);
    }

    public function test_claim_without_idempotency_header_returns_null_record(): void
    {
        ['actor' => $actor, 'tenant' => $tenant] = $this->createFixture();

        $request = $this->makeRequest($actor, '');

        $result = $this->service->claim($request, 'subscriptions', [
            'tenant_id' => $tenant->id,
            'amount' => 1500,
        ]);

        $this->assertNull($result['record']);
        $this->assertFalse($result['replayed']);
    }

    public function test_complete_with_null_record_does_nothing(): void
    {
        $this->service->complete(null, 201, 'subscription', 1);

        $this->assertDatabaseCount('idempotency_keys', 0);
    }

    private function makeRequest(User $actor, string $idempotencyKey): Request
    {
        $request = Request::create('/api/v1/subscriptions', 'POST');
        $request->headers->set('X-Idempotency-Key', $idempotencyKey);
        $request->setUserResolver(fn () => $actor);

        return $request;
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

        Model::reguard();

        return compact('tenant', 'branch', 'actor');
    }
}
