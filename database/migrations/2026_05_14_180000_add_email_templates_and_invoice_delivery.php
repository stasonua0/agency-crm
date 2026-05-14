<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_settings', function (Blueprint $table) {
            $table->string('invoice_email_subject')->nullable();
            $table->text('invoice_email_body')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('email_to')->nullable();
            $table->timestamp('email_sent_at')->nullable()->index();
            $table->jsonb('email_raw_response')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['email_to', 'email_sent_at', 'email_raw_response']);
        });

        Schema::table('studio_settings', function (Blueprint $table) {
            $table->dropColumn(['invoice_email_subject', 'invoice_email_body']);
        });
    }
};
