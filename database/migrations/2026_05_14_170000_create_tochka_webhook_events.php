<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('external_id');
        });

        Schema::create('tochka_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->nullable()->unique();
            $table->string('payload_hash')->unique();
            $table->string('external_id')->nullable()->index();
            $table->string('status')->default('received')->index();
            $table->text('message')->nullable();
            $table->jsonb('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tochka_webhook_events');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
        });
    }
};
