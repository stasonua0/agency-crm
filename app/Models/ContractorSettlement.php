<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContractorSettlement extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_PAID];

    protected $fillable = [
        'payment_occurrence_id',
        'payee_id',
        'payee_name_snapshot',
        'payee_requisites_snapshot',
        'amount',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(PaymentOccurrence::class, 'payment_occurrence_id');
    }

    public function payee(): BelongsTo
    {
        return $this->belongsTo(Payee::class);
    }

    public function batchItem(): HasOne
    {
        return $this->hasOne(PayoutBatchItem::class);
    }
}
