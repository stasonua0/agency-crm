<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_occurrence_id')->constrained()->restrictOnDelete();
            $table->foreignId('payee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payee_name_snapshot');
            $table->text('payee_requisites_snapshot')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique('payment_occurrence_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_settlements');
    }
};
