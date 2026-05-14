<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

class PfPayoutBatch extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_PAID = 'paid';

    public const STATUSES = [self::STATUS_PLANNED, self::STATUS_PAID];

    protected $fillable = [
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

    public function items(): HasMany
    {
        return $this->hasMany(PfPayoutBatchItem::class);
    }

    public function financialOperation(): HasOne
    {
        return $this->hasOne(FinancialOperation::class, 'source_pf_payout_batch_id');
    }

    public function markPaid($paidAt): void
    {
        DB::transaction(function () use ($paidAt) {
            $batch = self::query()
                ->whereKey($this->id)
                ->with('items.occurrence')
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
                if ($item->occurrence->status !== PaymentOccurrence::STATUS_PLANNED) {
                    throw new LogicException('В пакет попало неактуальное начисление ПФ.');
                }

                $item->occurrence->forceFill([
                    'status' => PaymentOccurrence::STATUS_PAID,
                    'paid_at' => $paidAt,
                ])->save();
            }

            FinancialOperation::firstOrCreate(
                [
                    'source' => FinancialOperation::SOURCE_PF_PAYOUT_BATCH,
                    'source_pf_payout_batch_id' => $batch->id,
                ],
                [
                    'type' => FinancialOperation::TYPE_EXPENSE,
                    'amount' => $batch->total_amount,
                    'paid_at' => $paidAt,
                    'category' => 'pf',
                    'comment' => 'Пакетная выплата ПФ',
                ]
            );
        });
    }
}
