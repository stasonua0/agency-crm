<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TochkaWebhookEvent extends Model
{
    use HasFactory;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_REQUIRES_ATTENTION = 'requires_attention';

    protected $fillable = [
        'event_id',
        'payload_hash',
        'external_id',
        'status',
        'message',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
