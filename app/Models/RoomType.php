<?php

namespace App\Models;

use App\Concerns\HasActiveKey;
use App\Enums\BookingPeriod;
use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $property_id
 * @property string $name
 * @property string|null $description
 * @property int $capacity
 * @property Property $property
 */
#[Fillable(['property_id', 'name', 'description', 'capacity'])]
class RoomType extends Model
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasActiveKey, HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return HasMany<RoomTypePrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(RoomTypePrice::class);
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activePriceFor(BookingPeriod $period): ?RoomTypePrice
    {
        return $this->prices()
            ->where('period', $period->value)
            ->where('is_active', true)
            ->first();
    }

    public function cheapestActivePrice(): ?RoomTypePrice
    {
        return $this->prices()
            ->where('is_active', true)
            ->orderBy('price')
            ->first();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }
}
