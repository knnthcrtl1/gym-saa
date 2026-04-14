<?php

namespace App\Support;

use Illuminate\Http\Request;

trait AuthorizesGymPermissions
{
    protected function requirePermission(Request $request, string|array $permissions): void
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasAnyPermission((array) $permissions),
            403,
            'You do not have permission to perform this action.',
        );
    }
}