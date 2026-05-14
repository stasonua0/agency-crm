<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'client_id',
        'name',
        'domain',
        'status',
        'budget',
        'paid_amount',
        'comment',
        'archived_at',
    ];

    protected $appends = [
        'remaining_amount',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    protected function remainingAmount(): Attribute
    {
        return Attribute::get(fn () => max(0, (float) $this->budget - (float) $this->paid_amount));
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('domain', 'ilike', "%{$search}%")
                    ->orWhere('comment', 'ilike', "%{$search}%")
                    ->orWhereHas('client', function (Builder $query) use ($search) {
                        $query
                            ->where('legal_name', 'ilike', "%{$search}%")
                            ->orWhere('short_name', 'ilike', "%{$search}%");
                    });
            });
        });
    }

    public function archive(): void
    {
        $this->forceFill([
            'status' => self::STATUS_ARCHIVED,
            'archived_at' => now(),
        ])->save();
    }
}
