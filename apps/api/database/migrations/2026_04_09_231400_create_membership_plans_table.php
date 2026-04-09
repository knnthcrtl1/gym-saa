<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('duration_type', 30);
            $table->unsignedInteger('duration_value');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('session_limit')->nullable();
            $table->unsignedInteger('freeze_limit_days')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};