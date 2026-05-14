<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_settings', function (Blueprint $table) {
            $table->string('ai_provider')->default('stub');
            $table->text('ai_api_key')->nullable();
            $table->string('ai_model')->nullable();
            $table->jsonb('ai_models_cache')->nullable();
            $table->timestamp('ai_models_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('studio_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_provider',
                'ai_api_key',
                'ai_model',
                'ai_models_cache',
                'ai_models_synced_at',
            ]);
        });
    }
};
