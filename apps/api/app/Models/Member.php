<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'member_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birthdate',
        'sex',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'qr_code_value',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'joined_at' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }
}