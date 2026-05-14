<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('payees')->nullOnDelete();
            $table->string('employee_name_snapshot');
            $table->text('requisites_snapshot')->nullable();
            $table->decimal('amount', 12, 2);
            $table->date('payout_date')->index();
            $table->string('type')->index();
            $table->string('status')->default('planned')->index();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::table('financial_operations', function (Blueprint $table) {
            $table->foreignId('source_payroll_payout_id')->nullable()->after('source_payout_batch_id')->constrained('payroll_payouts')->nullOnDelete();
            $table->unique(['source', 'source_payroll_payout_id']);
        });
    }

    public function down(): void
    {
        Schema::table('financial_operations', function (Blueprint $table) {
            $table->dropUnique(['source', 'source_payroll_payout_id']);
            $table->dropConstrainedForeignId('source_payroll_payout_id');
        });

        Schema::dropIfExists('payroll_payouts');
    }
};
