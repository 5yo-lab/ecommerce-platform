<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The `orders` table is owned by the NestJS migrations; orders are placed through the API.
 */
#[Fillable(['order_number', 'user_id', 'status', 'payment_status', 'payment_reference', 'subtotal', 'total'])]
class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    /** Allowed status changes; delivered and cancelled are final. */
    public const TRANSITIONS = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /** Orders that count as revenue in metrics and reports. */
    public const REVENUE_STATUSES = ['paid', 'processing', 'shipped', 'delivered'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function scopeRevenue(Builder $query): Builder
    {
        return $query->whereIn('status', self::REVENUE_STATUSES);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
            ->when($filters['min_total'] ?? null, fn (Builder $q, $min) => $q->where('total', '>=', $min))
            ->when($filters['max_total'] ?? null, fn (Builder $q, $max) => $q->where('total', '<=', $max))
            ->when($filters['customer'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $w) => $w
                ->where('order_number', 'like', "%{$term}%")
                ->orWhereHas('user', fn (Builder $u) => $u->withTrashed()->search($term))));
    }
}
