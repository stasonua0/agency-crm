<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

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
        'contractor_id_snapshot',
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

    protected static function booted(): void
    {
        static::updating(function (PaymentOccurrence $occurrence) {
            if (
                $occurrence->getOriginal('status') === self::STATUS_PAID
                && $occurrence->isDirty(['amount_snapshot', 'client_id', 'due_date'])
            ) {
                throw new LogicException('Оплаченное начисление нельзя менять. Используйте корректировку.');
            }
        });

        static::deleting(function (PaymentOccurrence $occurrence) {
            if ($occurrence->status === self::STATUS_PAID) {
                throw new LogicException('Оплаченное начисление нельзя удалять.');
            }
        });
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

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Payee::class, 'contractor_id_snapshot');
    }

    public function financialOperation(): HasOne
    {
        return $this->hasOne(FinancialOperation::class, 'source_occurrence_id');
    }

    public function contractorSettlement(): HasOne
    {
        return $this->hasOne(ContractorSettlement::class);
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

            if ((float) $this->contractor_amount_snapshot > 0) {
                $payee = $this->contractor_id_snapshot
                    ? Payee::query()->find($this->contractor_id_snapshot)
                    : null;

                ContractorSettlement::firstOrCreate(
                    [
                        'payment_occurrence_id' => $this->id,
                    ],
                    [
                        'payee_id' => $payee?->id,
                        'payee_name_snapshot' => $payee?->name ?? $this->contractor_name_snapshot ?? 'Подрядчик',
                        'payee_requisites_snapshot' => $payee?->requisites,
                        'amount' => $this->contractor_amount_snapshot,
                        'status' => ContractorSettlement::STATUS_PENDING,
                    ]
                );
            }
        });
    }

    public function createCorrection(string $type, $amount, $paidAt, ?string $comment = null): FinancialOperation
    {
        if ($this->status !== self::STATUS_PAID) {
            throw new LogicException('Корректировки доступны только для оплаченных начислений.');
        }

        return FinancialOperation::firstOrCreate(
            [
                'source' => FinancialOperation::SOURCE_CORRECTION,
                'source_occurrence_id' => $this->id,
            ],
            [
                'type' => $type,
                'client_id' => $this->client_id,
                'project_id' => $this->project_id,
                'service_id' => $this->service_id,
                'amount' => $amount,
                'paid_at' => $paidAt,
                'category' => 'correction',
                'comment' => $comment ?: "Корректировка начисления {$this->period}",
            ]
        );
    }
}
