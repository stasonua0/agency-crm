<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pf_payout_batches', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('planned')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('pf_payout_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pf_payout_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_occurrence_id')->constrained()->restrictOnDelete();
            $table->decimal('amount_snapshot', 12, 2);
            $table->timestamps();

            $table->unique('payment_occurrence_id');
        });

        Schema::table('financial_operations', function (Blueprint $table) {
            $table->foreignId('source_pf_payout_batch_id')->nullable()->after('source_payroll_payout_id')->constrained('pf_payout_batches')->nullOnDelete();
            $table->unique(['source', 'source_pf_payout_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('financial_operations', function (Blueprint $table) {
            $table->dropUnique(['source', 'source_pf_payout_batch_id']);
            $table->dropConstrainedForeignId('source_pf_payout_batch_id');
        });

        Schema::dropIfExists('pf_payout_batch_items');
        Schema::dropIfExists('pf_payout_batches');
    }
};
