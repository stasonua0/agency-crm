<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_SENT, self::STATUS_PAID, self::STATUS_CANCELLED];

    protected $fillable = [
        'occurrence_id',
        'client_id',
        'invoice_number',
        'invoice_date',
        'amount',
        'status',
        'invoice_url',
        'invoice_pdf_path',
        'external_id',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(PaymentOccurrence::class, 'occurrence_id');
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
                    ->where('invoice_number', 'ilike', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $query) => $query->where('short_name', 'ilike', "%{$search}%"));
            });
        });
    }
}
