<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'branch_id' => null,
            'actor_id' => null,
            'action' => $this->faker->randomElement([
                'payment.created',
                'subscription.created',
                'subscription.updated',
                'checkin.created',
            ]),
            'auditable_type' => 'App\\Models\\Subscription',
            'auditable_id' => $this->faker->numberBetween(1, 100),
            'summary' => $this->faker->sentence(),
            'before_state' => null,
            'after_state' => null,
            'changed_fields' => null,
            'metadata' => null,
        ];
    }
}
