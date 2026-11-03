<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function store(RoomRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $roomType = RoomType::query()->findOrFail($data['room_type_id']);

        if ($roomType->property_id !== (int) $data['property_id']) {
            throw ValidationException::withMessages([
                'room_type_id' => 'Tipe kamar harus berasal dari properti yang sama.',
            ]);
        }

        Room::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kamar ditambahkan.']);

        return back();
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $data = $request->validated();

        $roomType = RoomType::query()->findOrFail($data['room_type_id']);

        if ($roomType->property_id !== $room->property_id) {
            throw ValidationException::withMessages([
                'room_type_id' => 'Tipe kamar harus berasal dari properti yang sama.',
            ]);
        }

        $room->update([
            'room_type_id' => $data['room_type_id'],
            'name' => $data['name'],
            'status' => $data['status'] ?? $room->status,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kamar diperbarui.']);

        return back();
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->bookings()->whereIn('status', ['pending_payment', 'confirmed'])->exists()) {
            throw ValidationException::withMessages([
                'room' => 'Kamar masih memiliki booking aktif sehingga tidak dapat dihapus.',
            ]);
        }

        $room->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kamar dihapus.']);

        return back();
    }
}
