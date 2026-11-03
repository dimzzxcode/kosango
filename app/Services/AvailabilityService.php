<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    /**
     * @return Collection<int, Room>
     */
    public function availableRooms(RoomType $roomType, CarbonInterface $checkIn, CarbonInterface $checkOut): Collection
    {
        return Room::query()
            ->where('room_type_id', $roomType->id)
            ->where('status', RoomStatus::Available->value)
            ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut): void {
                $query
                    ->whereIn('status', [
                        BookingStatus::PendingPayment->value,
                        BookingStatus::Confirmed->value,
                    ])
                    ->where('check_in', '<', $checkOut->toDateString())
                    ->where('check_out', '>', $checkIn->toDateString());
            })
            ->orderBy('name')
            ->get();
    }

    public function isRoomAvailable(Room $room, CarbonInterface $checkIn, CarbonInterface $checkOut): bool
    {
        if ($room->status !== RoomStatus::Available) {
            return false;
        }

        return ! $room->bookings()
            ->whereIn('status', [
                BookingStatus::PendingPayment->value,
                BookingStatus::Confirmed->value,
            ])
            ->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString())
            ->exists();
    }
}
