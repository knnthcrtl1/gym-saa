<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->string('role', 30)->default('staff')->after('password');
            $table->string('status', 30)->default('active')->after('role');

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'role']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'branch_id']);
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['role', 'status']);
        });
    }
};