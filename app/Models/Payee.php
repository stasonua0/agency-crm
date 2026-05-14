<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payee extends Model
{
    use HasFactory;

    public const TYPE_EMPLOYEE = 'employee';

    public const TYPE_CONTRACTOR = 'contractor';

    public const TYPE_PF = 'pf';

    public const TYPE_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const TYPES = [self::TYPE_EMPLOYEE, self::TYPE_CONTRACTOR, self::TYPE_PF, self::TYPE_OTHER];

    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_ARCHIVED];

    protected $fillable = [
        'type',
        'name',
        'requisites',
        'phone',
        'comment',
        'status',
    ];

    public function recurringItems(): HasMany
    {
        return $this->hasMany(RecurringItem::class, 'contractor_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(ContractorSettlement::class);
    }

    public function archive(): void
    {
        $this->forceFill(['status' => self::STATUS_ARCHIVED])->save();
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('requisites', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('comment', 'ilike', "%{$search}%");
            });
        });
    }
}
