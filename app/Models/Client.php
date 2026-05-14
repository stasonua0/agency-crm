<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    public const TYPE_LEGAL_ENTITY = 'legal_entity';

    public const TYPE_INDIVIDUAL_ENTREPRENEUR = 'individual_entrepreneur';

    public const TYPE_INDIVIDUAL = 'individual';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const TYPES = [
        self::TYPE_LEGAL_ENTITY,
        self::TYPE_INDIVIDUAL_ENTREPRENEUR,
        self::TYPE_INDIVIDUAL,
    ];

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'type',
        'legal_name',
        'short_name',
        'inn',
        'kpp',
        'ogrn',
        'legal_address',
        'invoice_email',
        'contact_person',
        'phone',
        'comment',
        'status',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('legal_name', 'ilike', "%{$search}%")
                    ->orWhere('short_name', 'ilike', "%{$search}%")
                    ->orWhere('inn', 'ilike', "%{$search}%")
                    ->orWhere('contact_person', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
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
