<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamp('paid_at')->index();
            $table->string('category')->nullable()->index();
            $table->string('source')->index();
            $table->foreignId('source_occurrence_id')->nullable()->constrained('payment_occurrences')->nullOnDelete();
            $table->foreignId('source_payout_batch_id')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['source', 'source_occurrence_id']);
            $table->unique(['source', 'source_payout_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_operations');
    }
};
