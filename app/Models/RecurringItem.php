<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringItem extends Model
{
    use HasFactory;

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_SEMIANNUAL = 'semiannual';

    public const PERIOD_YEARLY = 'yearly';

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_STOPPED = 'stopped';

    public const TYPES = [self::TYPE_INCOME, self::TYPE_EXPENSE];

    public const PERIODICITIES = [self::PERIOD_MONTHLY, self::PERIOD_SEMIANNUAL, self::PERIOD_YEARLY];

    public const PAYMENT_METHODS = [self::METHOD_CASH, self::METHOD_BANK_TRANSFER];

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_STOPPED];

    protected $fillable = [
        'client_id',
        'project_id',
        'service_id',
        'operation_type',
        'amount',
        'periodicity',
        'start_date',
        'next_payment_date',
        'payment_method',
        'contractor_name',
        'contractor_amount',
        'status',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'contractor_amount' => 'decimal:2',
            'start_date' => 'date',
            'next_payment_date' => 'date',
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

    public function occurrences(): HasMany
    {
        return $this->hasMany(PaymentOccurrence::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('comment', 'ilike', "%{$search}%")
                    ->orWhere('contractor_name', 'ilike', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $query) => $query->where('short_name', 'ilike', "%{$search}%"))
                    ->orWhereHas('project', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('service', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%"));
            });
        });
    }

    public function periodForDate(CarbonInterface $date): string
    {
        return match ($this->periodicity) {
            self::PERIOD_YEARLY => $date->format('Y'),
            self::PERIOD_SEMIANNUAL => $date->format('Y').'-H'.($date->month <= 6 ? '1' : '2'),
            default => $date->format('Y-m'),
        };
    }
}
