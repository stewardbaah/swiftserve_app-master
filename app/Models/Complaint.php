<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $restaurant_id
 * @property int|null $user_id
 * @property int $order_id
 * @property string $reason
 * @property string $details
 * @property string $status
 * @property string|null $resolution_comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Complaint extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'order_id',
        'reason',
        'details',
        'status',
        'resolution_comment',
    ];

    /**
     * Get the restaurant associated with the complaint.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the customer (user) who filed the complaint.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the complaint.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
