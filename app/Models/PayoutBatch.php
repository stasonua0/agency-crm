<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

class PayoutBatch extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_PAID = 'paid';

    public const STATUSES = [self::STATUS_PLANNED, self::STATUS_PAID];

    protected $fillable = [
        'payee_id',
        'payee_name_snapshot',
        'payee_requisites_snapshot',
        'total_amount',
        'status',
        'paid_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(Payee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayoutBatchItem::class);
    }

    public function financialOperation(): HasOne
    {
        return $this->hasOne(FinancialOperation::class, 'source_payout_batch_id');
    }

    public function markPaid($paidAt): void
    {
        DB::transaction(function () use ($paidAt) {
            $batch = self::query()
                ->whereKey($this->id)
                ->with('items.settlement')
                ->lockForUpdate()
                ->firstOrFail();

            if ($batch->status === self::STATUS_PAID) {
                return;
            }

            $batch->forceFill([
                'status' => self::STATUS_PAID,
                'paid_at' => $paidAt,
            ])->save();

            foreach ($batch->items as $item) {
                if ($item->settlement->status !== ContractorSettlement::STATUS_PENDING) {
                    throw new LogicException('В пакет попала неактуальная выплата.');
                }

                $item->settlement->forceFill([
                    'status' => ContractorSettlement::STATUS_PAID,
                    'paid_at' => $paidAt,
                ])->save();
            }

            FinancialOperation::firstOrCreate(
                [
                    'source' => FinancialOperation::SOURCE_PAYOUT_BATCH,
                    'source_payout_batch_id' => $batch->id,
                ],
                [
                    'type' => FinancialOperation::TYPE_EXPENSE,
                    'amount' => $batch->total_amount,
                    'paid_at' => $paidAt,
                    'category' => 'contractor_payout',
                    'comment' => "Выплата получателю {$batch->payee_name_snapshot}",
                ]
            );
        });
    }
}
