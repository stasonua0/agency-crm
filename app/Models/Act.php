<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Act extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_SIGNATURE = 'awaiting_signature';

    public const STATUS_SENT_TO_EDO = 'sent_to_edo';

    public const STATUS_SIGNED = 'signed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_AWAITING_SIGNATURE, self::STATUS_SENT_TO_EDO, self::STATUS_SIGNED, self::STATUS_CANCELLED];

    protected $fillable = [
        'occurrence_id',
        'invoice_id',
        'client_id',
        'act_number',
        'act_date',
        'amount',
        'status',
        'file_path',
        'external_id',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'act_date' => 'date',
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(PaymentOccurrence::class, 'occurrence_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('act_number', 'ilike', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $query) => $query->where('short_name', 'ilike', "%{$search}%"));
            });
        });
    }
}
