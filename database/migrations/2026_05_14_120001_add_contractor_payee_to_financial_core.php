<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurring_items', function (Blueprint $table) {
            $table->foreignId('contractor_id')->nullable()->after('payment_method')->constrained('payees')->nullOnDelete();
        });

        Schema::table('payment_occurrences', function (Blueprint $table) {
            $table->foreignId('contractor_id_snapshot')->nullable()->after('operation_type')->constrained('payees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_occurrences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contractor_id_snapshot');
        });

        Schema::table('recurring_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contractor_id');
        });
    }
};
