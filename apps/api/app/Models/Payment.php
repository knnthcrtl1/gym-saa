<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'member_id',
        'subscription_id',
        'gateway',
        'currency',
        'gateway_checkout_session_id',
        'gateway_payment_id',
        'gateway_reference',
        'checkout_url',
        'gateway_metadata',
        'raw_response',
        'payment_date',
        'paid_at',
        'amount',
        'payment_method',
        'reference_no',
        'notes',
        'status',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'gateway_metadata' => 'array',
            'raw_response' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(PaymentWebhook::class);
    }
}