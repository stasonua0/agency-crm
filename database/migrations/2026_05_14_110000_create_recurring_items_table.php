<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('operation_type')->index();
            $table->decimal('amount', 12, 2);
            $table->string('periodicity')->index();
            $table->date('start_date');
            $table->date('next_payment_date')->index();
            $table->string('payment_method')->index();
            $table->string('contractor_name')->nullable();
            $table->decimal('contractor_amount', 12, 2)->nullable();
            $table->string('status')->default('active')->index();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_items');
    }
};
