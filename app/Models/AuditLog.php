<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    use HasFactory;

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_ARCHIVED = 'archived';

    public const ACTION_PAID = 'paid';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_CORRECTION = 'correction';

    public const ACTION_INVOICE_SENT = 'invoice_sent';

    public const ACTION_WEBHOOK = 'webhook';

    public const ACTION_BATCH_PAYOUT = 'batch_payout';

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'ip',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log нельзя редактировать.'));
        static::deleting(fn () => throw new LogicException('Audit log нельзя удалять.'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query
                    ->where('action', 'ilike', "%{$search}%")
                    ->orWhere('auditable_type', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%"));
            });
        });
    }
}
