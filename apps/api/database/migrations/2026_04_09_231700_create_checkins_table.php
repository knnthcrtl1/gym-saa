<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('checkin_time');
            $table->dateTime('checkout_time')->nullable();
            $table->enum('source', ['qr', 'manual', 'kiosk'])->default('qr');
            $table->enum('status', ['checked_in', 'checked_out'])->default('checked_in');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['member_id', 'checkin_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};