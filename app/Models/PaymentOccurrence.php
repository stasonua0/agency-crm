<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class PaymentOccurrence extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_PLANNED, self::STATUS_PAID, self::STATUS_CANCELLED];

    protected $fillable = [
        'recurring_item_id',
        'client_id',
        'project_id',
        'service_id',
        'amount_snapshot',
        'contractor_amount_snapshot',
        'contractor_name_snapshot',
        'period',
        'due_date',
        'payment_method',
        'operation_type',
        'status',
        'paid_at',
        'invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_snapshot' => 'decimal:2',
            'contractor_amount_snapshot' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function recurringItem(): BelongsTo
    {
        return $this->belongsTo(RecurringItem::class);
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

    public function financialOperation(): HasOne
    {
        return $this->hasOne(FinancialOperation::class, 'source_occurrence_id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('period', 'ilike', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $query) => $query->where('short_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('project', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('service', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%"));
            });
        });
    }

    public function markPaid($paidAt): void
    {
        DB::transaction(function () use ($paidAt) {
            $this->forceFill([
                'status' => self::STATUS_PAID,
                'paid_at' => $paidAt,
            ])->save();

            FinancialOperation::firstOrCreate(
                [
                    'source' => FinancialOperation::SOURCE_OCCURRENCE,
                    'source_occurrence_id' => $this->id,
                ],
                [
                    'type' => $this->operation_type,
                    'client_id' => $this->client_id,
                    'project_id' => $this->project_id,
                    'service_id' => $this->service_id,
                    'amount' => $this->amount_snapshot,
                    'paid_at' => $paidAt,
                    'category' => 'occurrence',
                    'comment' => "Оплата начисления {$this->period}",
                ]
            );
        });
    }
}
