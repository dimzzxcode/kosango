<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomTypeRequest;
use App\Models\Property;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RoomTypeController extends Controller
{
    public function store(RoomTypeRequest $request, Property $property): RedirectResponse
    {
        $property->roomTypes()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tipe kamar ditambahkan. Jangan lupa mengisi tarif.',
        ]);

        return back();
    }

    public function update(RoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        $roomType->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tipe kamar diperbarui.']);

        return back();
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        if ($roomType->rooms()->exists()) {
            throw ValidationException::withMessages([
                'room_type' => 'Tipe kamar masih memiliki kamar sehingga tidak dapat dihapus.',
            ]);
        }

        $roomType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tipe kamar dihapus.']);

        return back();
    }
}
