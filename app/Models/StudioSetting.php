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
    ];

    protected function casts(): array
    {
        return [
            'vat_enabled' => 'boolean',
        ];
    }

    public static function singleton(): self
    {
        return self::query()->firstOrCreate([]);
    }
}
