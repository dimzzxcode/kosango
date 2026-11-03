<?php

namespace App\Models;

use App\Concerns\HasActiveKey;
use App\Enums\BookingStatus;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\RoomStatus;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $city_id
 * @property int|null $created_by
 * @property PropertyType $type
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $address
 * @property string|null $whatsapp_number
 * @property PropertyStatus $status
 * @property string|null $thumbnail_path
 * @property string|null $thumbnail_url
 * @property string|null $thumbnail_alt
 * @property float $rating_avg
 * @property int $reviews_count
 * @property string $accent
 * @property-read string|null $whatsapp_url
 * @property City $city
 */
#[Fillable([
    'city_id', 'created_by', 'type', 'name', 'slug', 'description', 'address',
    'latitude', 'longitude', 'whatsapp_number', 'status', 'thumbnail_path',
    'thumbnail_alt', 'rating_avg', 'reviews_count',
])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasActiveKey, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = ['thumbnail_url', 'accent', 'whatsapp_url'];

    /**
     * Properties are addressed by their slug in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PropertyImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<Facility, $this>
     */
    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'facility_property');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_user');
    }

    /**
     * @return HasMany<RoomType, $this>
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<BankAccount, $this>
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @param  Builder<Property>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PropertyStatus::Published->value);
    }

    /**
     * @param  Builder<Property>  $query
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['city'] ?? null, fn (Builder $q, string $city) => $q->whereHas(
                'city', fn (Builder $cityQuery) => $cityQuery->where('slug', $city),
            ))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['q'] ?? null, function (Builder $q, string $term): void {
                $q->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('address', 'like', "%{$term}%")
                        ->orWhereHas('city', fn (Builder $city) => $city->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['guests'] ?? null, fn (Builder $q, int|string $guests) => $q->whereHas(
                'roomTypes', fn (Builder $roomType) => $roomType->where('capacity', '>=', (int) $guests),
            ))
            ->when(
                filled($filters['period'] ?? null)
                    || filled($filters['min_price'] ?? null)
                    || filled($filters['max_price'] ?? null),
                function (Builder $q) use ($filters): void {
                    $q->whereHas('roomTypes.prices', function (Builder $price) use ($filters): void {
                        $price->where('is_active', true);

                        if (filled($filters['period'] ?? null)) {
                            $price->where('period', $filters['period']);
                        }

                        if (filled($filters['min_price'] ?? null)) {
                            $price->where('price', '>=', (int) $filters['min_price']);
                        }

                        if (filled($filters['max_price'] ?? null)) {
                            $price->where('price', '<=', (int) $filters['max_price']);
                        }
                    });
                },
            );
    }

    public function isPublished(): bool
    {
        return $this->status === PropertyStatus::Published;
    }

    public function canBePublished(): bool
    {
        $hasPricedType = $this->roomTypes()
            ->whereHas('prices', fn (Builder $query) => $query->where('is_active', true))
            ->whereHas('rooms', fn (Builder $query) => $query->where('status', RoomStatus::Available->value))
            ->exists();

        return $hasPricedType && filled($this->thumbnail_path);
    }

    public function cheapestActivePrice(): ?RoomTypePrice
    {
        return RoomTypePrice::query()
            ->whereIn('room_type_id', $this->roomTypes()->select('id'))
            ->where('is_active', true)
            ->orderBy('price')
            ->first();
    }

    public function averageRating(): float
    {
        $average = $this->reviews()
            ->where('is_visible', true)
            ->avg('rating');

        return round((float) $average, 2);
    }

    public function activeBookingsAt(string $checkIn, string $checkOut): Builder
    {
        return Booking::query()
            ->where('property_id', $this->id)
            ->whereIn('status', [
                BookingStatus::PendingPayment->value,
                BookingStatus::Confirmed->value,
            ])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function accent(): Attribute
    {
        return Attribute::get(function (): string {
            $accents = ['jade', 'ocean', 'sunset', 'violet', 'amber'];

            return $accents[$this->id % count($accents)];
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->thumbnail_path
                ? Storage::disk('public')->url($this->thumbnail_path)
                : null,
        );
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function whatsappUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (blank($this->whatsapp_number)) {
                return null;
            }

            $number = Str::of($this->whatsapp_number)->replaceMatches('/[^0-9]/', '');

            if (Str::startsWith($number, '0')) {
                $number = '62'.Str::after($number, '0');
            }

            return 'https://wa.me/'.$number;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
            'status' => PropertyStatus::class,
            'rating_avg' => 'float',
            'reviews_count' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
