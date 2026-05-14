<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'name',
        'document_name',
        'status',
        'comment',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('document_name', 'ilike', "%{$search}%")
                    ->orWhere('comment', 'ilike', "%{$search}%");
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
