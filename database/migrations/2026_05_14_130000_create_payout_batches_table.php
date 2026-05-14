<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payee_name_snapshot');
            $table->text('payee_requisites_snapshot')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('planned')->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('payout_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_settlement_id')->constrained()->restrictOnDelete();
            $table->decimal('amount_snapshot', 12, 2);
            $table->timestamps();

            $table->unique('contractor_settlement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_batch_items');
        Schema::dropIfExists('payout_batches');
    }
};
