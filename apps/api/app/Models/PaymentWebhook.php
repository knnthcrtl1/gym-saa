<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'payment_id',
        'provider_event_id',
        'event_type',
        'resource_type',
        'resource_id',
        'signature_verified',
        'headers',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'headers' => 'array',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}