<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->string('ogrn')->nullable();
            $table->text('address')->nullable();
            $table->string('bank')->nullable();
            $table->string('checking_account')->nullable();
            $table->string('correspondent_account')->nullable();
            $table->string('bik')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('vat_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occurrence_id')->constrained('payment_occurrences')->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('draft')->index();
            $table->string('invoice_url')->nullable();
            $table->string('invoice_pdf_path')->nullable();
            $table->string('external_id')->nullable();
            $table->jsonb('raw_response')->nullable();
            $table->timestamps();

            $table->unique('occurrence_id');
        });

        Schema::create('acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occurrence_id')->constrained('payment_occurrences')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('act_number')->unique();
            $table->date('act_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('awaiting_signature')->index();
            $table->string('file_path')->nullable();
            $table->string('external_id')->nullable();
            $table->jsonb('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acts');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('studio_settings');
    }
};
