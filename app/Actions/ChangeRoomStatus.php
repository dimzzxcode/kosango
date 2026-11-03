<?php

namespace App\Actions;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Validation\ValidationException;

class ChangeRoomStatus
{
    public function __construct(private readonly Notifier $notifier) {}

    public function handle(Room $room, User $actor, RoomStatus $status): Room
    {
        $room->loadMissing('property');

        if (! $actor->canManageProperty($room->property)) {
            throw ValidationException::withMessages(['room' => 'Anda tidak berhak mengubah status kamar ini.']);
        }

        $room->update(['status' => $status]);

        if ($status === RoomStatus::Maintenance) {
            $hasActiveBooking = $room->bookings()
                ->whereIn('status', ['pending_payment', 'confirmed'])
                ->where('check_out', '>=', today()->toDateString())
                ->exists();

            if ($hasActiveBooking) {
                $this->notifier->toSuperadmins(
                    'Kamar maintenance dengan booking aktif',
                    "Kamar {$room->name} di {$room->property->name} diubah ke maintenance saat masih memiliki booking aktif.",
                    route('admin.rooms.index', $room->property),
                );
            }
        }

        return $room->refresh();
    }
}
