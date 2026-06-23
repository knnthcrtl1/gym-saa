<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable();
            $table->string('currency', 3)->default('PHP');
            $table->string('gateway_checkout_session_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->text('checkout_url')->nullable();
            $table->json('gateway_metadata')->nullable();
            $table->json('raw_response')->nullable();
            $table->dateTime('paid_at')->nullable();

            $table->index(['gateway', 'gateway_checkout_session_id']);
            $table->index(['gateway', 'gateway_payment_id']);
        });

        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('paymongo');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_event_id')->unique();
            $table->string('event_type')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'event_type']);
            $table->index(['provider', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway', 'gateway_checkout_session_id']);
            $table->dropIndex(['gateway', 'gateway_payment_id']);
            $table->dropColumn([
                'gateway',
                'currency',
                'gateway_checkout_session_id',
                'gateway_payment_id',
                'gateway_reference',
                'checkout_url',
                'gateway_metadata',
                'raw_response',
                'paid_at',
            ]);
        });
    }
};