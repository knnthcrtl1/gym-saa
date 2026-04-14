<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\GymPermission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['tenant_id', 'branch_id', 'name', 'email', 'password', 'role', 'staff_role', 'status', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public function effectivePermissions(): array
    {
        if ($this->role === 'super_admin') {
            return ['*'];
        }

        if (is_array($this->permissions)) {
            return array_values(array_unique(array_filter($this->permissions, 'is_string')));
        }

        return GymPermission::defaultFor($this->role, $this->staff_role);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->effectivePermissions();

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function toAuthArray(): array
    {
        return array_merge(
            Arr::only($this->toArray(), ['id', 'tenant_id', 'branch_id', 'name', 'email', 'role', 'staff_role', 'status']),
            ['permissions' => $this->effectivePermissions()],
        );
    }

    public function toStaffArray(): array
    {
        $data = $this->toArray();
        $data['permissions'] = $this->effectivePermissions();

        return $data;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function reviewedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'reviewed_by');
    }

    public function uploadedPaymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class, 'uploaded_by');
    }

    public function verifiedCheckins(): HasMany
    {
        return $this->hasMany(Checkin::class, 'verified_by');
    }
}
