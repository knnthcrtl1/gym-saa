<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('payment_date');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 30);
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('completed');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['member_id', 'payment_date']);
            $table->index('reference_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};