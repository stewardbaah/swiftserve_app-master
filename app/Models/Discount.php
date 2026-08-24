<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $restaurant_id
 * @property string $code
 * @property string $type
 * @property int $value
 * @property int $min_order_amount
 * @property bool $is_active
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Discount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'restaurant_id',
        'code',
        'type',
        'value',
        'min_order_amount',
        'is_active',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the restaurant that owns the discount.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Check if the discount has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Calculate the discount amount in cents based on the subtotal in cents.
     */
    public function calculateDiscount(int $subtotalInCents): int
    {
        if (! $this->is_active || $this->isExpired()) {
            return 0;
        }

        if ($subtotalInCents < $this->min_order_amount) {
            return 0;
        }

        if ($this->type === 'fixed') {
            return min($subtotalInCents, $this->value);
        }

        if ($this->type === 'percentage') {
            return (int) round($subtotalInCents * ($this->value / 100));
        }

        return 0;
    }
}
