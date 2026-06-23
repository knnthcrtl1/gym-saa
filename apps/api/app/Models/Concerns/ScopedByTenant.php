<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

trait ScopedByTenant
{
    public static function bootScopedByTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
