<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GymPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_on_success(): void
    {
        ['actor' => $actor] = $this->createFixture();

        $response = $this->postJson('/api/v1/login', [
            'email' => $actor->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'tenant_id', 'branch_id', 'name', 'email', 'role', 'staff_role', 'status', 'permissions'],
                'token',
            ])
            ->assertJsonPath('user.id', $actor->id)
            ->assertJsonPath('user.email', $actor->email);
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        ['actor' => $actor] = $this->createFixture();

        $this->postJson('/api/v1/login', [
            'email' => $actor->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_with_missing_fields_returns_422(): void
    {
        $this->postJson('/api/v1/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_logout_with_valid_token_succeeds(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully');
    }

    public function test_logout_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/v1/logout')
            ->assertUnauthorized();
    }

    public function test_me_returns_user_data(): void
    {
        ['actor' => $actor] = $this->createFixture();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonStructure([
                'user' => ['id', 'tenant_id', 'branch_id', 'name', 'email', 'role', 'staff_role', 'status', 'permissions'],
            ])
            ->assertJsonPath('user.id', $actor->id)
            ->assertJsonPath('user.email', $actor->email);
    }

    public function test_me_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    private function createFixture(): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Auth Test Gym',
            'slug' => 'auth-test-gym-'.uniqid(),
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
