<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('legal_name');
            $table->string('short_name');
            $table->string('inn')->nullable()->index();
            $table->string('kpp')->nullable();
            $table->string('ogrn')->nullable();
            $table->text('legal_address')->nullable();
            $table->string('invoice_email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
