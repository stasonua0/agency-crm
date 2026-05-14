<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'inn',
        'kpp',
        'ogrn',
        'address',
        'bank',
        'checking_account',
        'correspondent_account',
        'bik',
        'email',
        'phone',
        'vat_enabled',
        'invoice_email_subject',
        'invoice_email_body',
        'ai_provider',
        'ai_api_key',
        'ai_model',
        'ai_models_cache',
        'ai_models_synced_at',
    ];

    protected $hidden = [
        'ai_api_key',
    ];

    protected function casts(): array
    {
        return [
            'vat_enabled' => 'boolean',
            'ai_api_key' => 'encrypted',
            'ai_models_cache' => 'array',
            'ai_models_synced_at' => 'datetime',
        ];
    }

    public function hasAiApiKey(): bool
    {
        return filled($this->ai_api_key);
    }

    public static function singleton(): self
    {
        return self::query()->firstOrCreate([]);
    }
}
