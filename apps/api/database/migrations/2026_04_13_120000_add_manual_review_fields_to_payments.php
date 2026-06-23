<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('verification_status', ['not_required', 'pending', 'verified', 'rejected'])
                ->default('not_required')
                ->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('verification_status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('reviewed_by');

            $table->index(['status', 'verification_status']);
        });

        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'verification_status']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'verification_status',
                'reviewed_at',
                'review_notes',
            ]);
        });
    }
};