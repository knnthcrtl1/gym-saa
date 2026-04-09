<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->after('phone');
            $table->string('sex', 20)->nullable()->after('birthdate');
            $table->text('address')->nullable()->after('sex');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->string('qr_code_value')->nullable()->unique()->after('emergency_contact_phone');
            $table->timestamp('joined_at')->nullable()->after('status');

            $table->index(['tenant_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['branch_id', 'status']);
            $table->dropUnique(['qr_code_value']);
            $table->dropColumn([
                'birthdate',
                'sex',
                'address',
                'emergency_contact_name',
                'emergency_contact_phone',
                'qr_code_value',
                'joined_at',
            ]);
        });
    }
};