<?php

namespace App\Models;

use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $team_id
 * @property int $city_id
 * @property string $name
 * @property string $slug
 * @property string $cuisine_type
 * @property string|null $description
 * @property string $address
 * @property float|null $latitude
 * @property float|null $longitude
 * @property int $delivery_radius_km
 * @property string|null $phone
 * @property array|null $operating_hours
 * @property int $delivery_fee
 * @property int $estimated_delivery_time
 * @property int $estimated_pickup_time
 * @property float $rating
 * @property int $rating_count
 * @property string|null $image_path
 * @property bool $offers_delivery
 * @property bool $offers_pickup
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'team_id',
    'city_id',
    'name',
    'slug',
    'cuisine_type',
    'description',
    'address',
    'latitude',
    'longitude',
    'delivery_radius_km',
    'phone',
    'operating_hours',
    'delivery_fee',
    'estimated_delivery_time',
    'estimated_pickup_time',
    'rating',
    'rating_count',
    'image_path',
    'offers_delivery',
    'offers_pickup',
    'is_active',
    'is_approved',
])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['image_url'];

    /**
     * Get the restaurant's image URL.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? Storage::url($this->image_path) : null,
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'offers_delivery' => 'boolean',
            'offers_pickup' => 'boolean',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'rating' => 'float',
            'rating_count' => 'integer',
            'delivery_fee' => 'integer',
            'delivery_radius_km' => 'integer',
            'estimated_delivery_time' => 'integer',
            'estimated_pickup_time' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Scope a query to only include approved restaurants.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Check whether the given coordinates fall within this restaurant's delivery radius.
     * Returns null when the restaurant's own coordinates are not configured.
     */
    public function deliversToCoordinates(float $lat, float $lng): ?bool
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        $earthRadius = 6371;
        $dLat = deg2rad($lat - $this->latitude);
        $dLon = deg2rad($lng - $this->longitude);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($this->latitude)) * cos(deg2rad($lat))
            * sin($dLon / 2) * sin($dLon / 2);

        $distanceKm = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $distanceKm <= $this->delivery_radius_km;
    }

    /**
     * Get the team that owns the restaurant.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the city where the restaurant is located.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the cities where this restaurant delivers to or operates in.
     */
    public function deliveryCities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'city_restaurant')->withTimestamps();
    }

    /**
     * Get the menu categories for the restaurant.
     */
    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class)->orderBy('sort_order');
    }

    /**
     * Get the menu items for the restaurant.
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Get the orders received by the restaurant.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the reviews for the restaurant.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the users who favorited this restaurant.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * Get the taxes configured for the restaurant.
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    /**
     * Get the discounts configured for the restaurant.
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * Get the complaints received by the restaurant.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
