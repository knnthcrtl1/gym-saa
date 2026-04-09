<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait BelongsToTenant
{
    protected function scopeToTenant(Builder $query, Request $request): Builder
    {
        $user = $request->user();

        if (! $user) {
            return $query;
        }

        if ($user->role === 'super_admin') {
            return $query;
        }

        return $query->where('tenant_id', $user->tenant_id);
    }

    protected function scopeToBranchIfStaff(Builder $query, Request $request): Builder
    {
        $user = $request->user();

        if (! $user) {
            return $query;
        }

        if (in_array($user->role, ['super_admin', 'gym_admin'], true)) {
            return $query;
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}