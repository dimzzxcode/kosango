<?php

namespace App\Http\Controllers\Superadmin;

use App\Enums\BookingPeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomTypePricesRequest;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RoomTypePriceController extends Controller
{
    /**
     * Replace the active rates of a room type per period.
     */
    public function update(RoomTypePricesRequest $request, RoomType $roomType): RedirectResponse
    {
        foreach ($request->validated('prices') as $row) {
            $roomType->prices()->updateOrCreate(
                ['period' => $row['period']],
                [
                    'price' => (int) $row['price'],
                    'min_duration' => (int) $row['min_duration'],
                    'max_duration' => (int) $row['max_duration'],
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tarif tipe kamar diperbarui.']);

        return back();
    }

    /**
     * Default rate rows used by the management form.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(RoomType $roomType): array
    {
        return collect(BookingPeriod::cases())->map(function (BookingPeriod $period) use ($roomType): array {
            $rate = $roomType->prices->firstWhere('period', $period);

            return [
                'period' => $period->value,
                'label' => $period->label(),
                'unit' => $period->unitLabel(),
                'price' => $rate?->price ?? 0,
                'min_duration' => $rate?->min_duration ?? 1,
                'max_duration' => $rate?->max_duration ?? $period->defaultMaxDuration(),
                'is_active' => $rate?->is_active ?? false,
            ];
        })->all();
    }
}
