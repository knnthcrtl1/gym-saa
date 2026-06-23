<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        string $action,
        Model $auditable,
        ?User $actor,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
        ?string $summary = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'tenant_id' => $auditable->getAttribute('tenant_id') ?? $actor?->tenant_id,
            'branch_id' => $auditable->getAttribute('branch_id') ?? $actor?->branch_id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'summary' => $summary,
            'before_state' => $before,
            'after_state' => $after,
            'changed_fields' => $this->changedFields($before, $after),
            'metadata' => $metadata,
        ]);
    }

    private function changedFields(?array $before, ?array $after): ?array
    {
        if ($before === null && $after === null) {
            return null;
        }

        if ($before === null) {
            return array_values(array_keys($after ?? []));
        }

        if ($after === null) {
            return array_values(array_keys($before));
        }

        $changed = [];

        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        return array_values($changed);
    }
}