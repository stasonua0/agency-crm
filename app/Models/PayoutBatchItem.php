<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_batch_id',
        'contractor_settlement_id',
        'amount_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'amount_snapshot' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayoutBatch::class, 'payout_batch_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(ContractorSettlement::class, 'contractor_settlement_id');
    }
}
