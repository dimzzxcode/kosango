<?php

namespace App\Models;

use App\Enums\BookingPeriod;
use Database\Factories\RoomTypePriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $room_type_id
 * @property BookingPeriod $period
 * @property int $price
 * @property int $min_duration
 * @property int $max_duration
 * @property bool $is_active
 * @property RoomType $roomType
 */
#[Fillable(['room_type_id', 'period', 'price', 'min_duration', 'max_duration', 'is_active'])]
class RoomTypePrice extends Model
{
    /** @use HasFactory<RoomTypePriceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => BookingPeriod::class,
            'price' => 'integer',
            'min_duration' => 'integer',
            'max_duration' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
