<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialOperation extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_OCCURRENCE = 'occurrence';

    public const SOURCE_CORRECTION = 'correction';

    public const SOURCE_PAYOUT_BATCH = 'payout_batch';

    public const TYPES = [self::TYPE_INCOME, self::TYPE_EXPENSE];

    public const SOURCES = [self::SOURCE_MANUAL, self::SOURCE_OCCURRENCE, self::SOURCE_CORRECTION, self::SOURCE_PAYOUT_BATCH];

    protected $fillable = [
        'type',
        'client_id',
        'project_id',
        'service_id',
        'amount',
        'paid_at',
        'category',
        'source',
        'source_occurrence_id',
        'source_payout_batch_id',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(PaymentOccurrence::class, 'source_occurrence_id');
    }

    public function payoutBatch(): BelongsTo
    {
        return $this->belongsTo(PayoutBatch::class, 'source_payout_batch_id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('category', 'ilike', "%{$search}%")
                    ->orWhere('comment', 'ilike', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $query) => $query->where('short_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('project', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('service', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%"));
            });
        });
    }
}
