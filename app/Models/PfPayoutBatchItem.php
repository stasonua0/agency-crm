<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PfPayoutBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pf_payout_batch_id',
        'payment_occurrence_id',
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
        return $this->belongsTo(PfPayoutBatch::class, 'pf_payout_batch_id');
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(PaymentOccurrence::class, 'payment_occurrence_id');
    }
}
