<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurring_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->decimal('amount_snapshot', 12, 2);
            $table->decimal('contractor_amount_snapshot', 12, 2)->nullable();
            $table->string('contractor_name_snapshot')->nullable();
            $table->string('period')->index();
            $table->date('due_date')->index();
            $table->string('payment_method')->index();
            $table->string('operation_type')->index();
            $table->string('status')->default('planned')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->timestamps();

            $table->unique(['recurring_item_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_occurrences');
    }
};
