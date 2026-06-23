<?php

namespace App\Http\Requests\Concerns;

trait AuthorizesGymPermission
{
    protected function userCan(string|array $permissions): bool
    {
        $user = $this->user();

        return (bool) ($user && $user->hasAnyPermission((array) $permissions));
    }
}