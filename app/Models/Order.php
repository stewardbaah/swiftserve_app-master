<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $restaurant_id
 * @property string $order_number
 * @property string $status
 * @property string $type
 * @property string|null $delivery_address
 * @property int $subtotal
 * @property int $delivery_fee
 * @property int $service_fee
 * @property int $tax
 * @property int $discount
 * @property int $total
 * @property string|null $special_instructions
 * @property Carbon|null $estimated_ready_time
 * @property string $payment_status
 * @property string $payment_method
 * @property string|null $promo_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'restaurant_id',
    'order_number',
    'status',
    'type',
    'delivery_address',
    'subtotal',
    'delivery_fee',
    'service_fee',
    'tax',
    'discount',
    'total',
    'special_instructions',
    'estimated_ready_time',
    'payment_status',
    'payment_method',
    'promo_code',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'restaurant_id' => 'integer',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'service_fee' => 'integer',
            'tax' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'estimated_ready_time' => 'datetime',
        ];
    }

    /**
     * Get the customer who placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the restaurant receiving the order.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the items in this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the review associated with the order.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Get the complaint associated with the order.
     */
    public function complaint(): HasOne
    {
        return $this->hasOne(Complaint::class);
    }
}
