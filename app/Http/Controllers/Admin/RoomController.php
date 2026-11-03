<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ChangeRoomStatus;
use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeRoomStatusRequest;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * List rooms of a managed property together with their status.
     */
    public function index(Request $request, Property $property): Response
    {
        abort_unless($request->user()->canManageProperty($property), 403);

        $property->load([
            'city',
            'roomTypes' => fn ($query) => $query->withCount('rooms'),
        ]);

        return Inertia::render('admin/rooms/Index', [
            'property' => $property,
            'rooms' => $property->rooms()
                ->with(['roomType'])
                ->withCount([
                    'bookings as active_bookings_count' => fn ($query) => $query
                        ->whereIn('status', ['pending_payment', 'confirmed'])
                        ->where('check_out', '>=', today()->toDateString()),
                ])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Update a room status (available or maintenance).
     */
    public function updateStatus(
        ChangeRoomStatusRequest $request,
        Room $room,
        ChangeRoomStatus $changeRoomStatus,
    ): RedirectResponse {
        $changeRoomStatus->handle($room, $request->user(), RoomStatus::from($request->validated('status')));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Status kamar diperbarui.',
        ]);

        return back();
    }
}
