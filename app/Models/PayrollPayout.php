<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class PayrollPayout extends Model
{
    use HasFactory;

    public const TYPE_SALARY = 'salary';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_ADVANCE = 'advance';

    public const STATUS_PLANNED = 'planned';

    public const STATUS_PAID = 'paid';

    public const TYPES = [self::TYPE_SALARY, self::TYPE_BONUS, self::TYPE_ADVANCE];

    public const STATUSES = [self::STATUS_PLANNED, self::STATUS_PAID];

    protected $fillable = [
        'employee_id',
        'employee_name_snapshot',
        'requisites_snapshot',
        'amount',
        'payout_date',
        'type',
        'status',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payout_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Payee::class, 'employee_id');
    }

    public function financialOperation(): HasOne
    {
        return $this->hasOne(FinancialOperation::class, 'source_payroll_payout_id');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('employee_name_snapshot', 'ilike', "%{$search}%")
                    ->orWhere('comment', 'ilike', "%{$search}%");
            });
        });
    }

    public function markPaid(): void
    {
        DB::transaction(function () {
            $payout = self::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($payout->status === self::STATUS_PAID && $payout->financialOperation()->exists()) {
                return;
            }

            $this->forceFill(['status' => self::STATUS_PAID])->save();

            FinancialOperation::firstOrCreate(
                [
                    'source' => FinancialOperation::SOURCE_PAYROLL,
                    'source_payroll_payout_id' => $this->id,
                ],
                [
                    'type' => FinancialOperation::TYPE_EXPENSE,
                    'amount' => $this->amount,
                    'paid_at' => $this->payout_date,
                    'category' => 'payroll',
                    'comment' => "Зарплатная выплата {$this->employee_name_snapshot}",
                ]
            );
        });
    }
}
