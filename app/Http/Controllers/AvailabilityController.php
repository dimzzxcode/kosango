<?php

namespace App\Http\Controllers;

use App\Enums\BookingPeriod;
use App\Enums\PaymentPlan;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    public function __invoke(
        Request $request,
        AvailabilityService $availability,
        PricingService $pricing,
    ): JsonResponse {
        $data = $request->validate([
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'period' => ['required', 'in:daily,monthly,yearly'],
            'duration' => ['required', 'integer', 'min:1', 'max:120'],
            'payment_plan' => ['required', 'in:full,per_period,installment'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $roomType = RoomType::query()->with('prices')->findOrFail($data['room_type_id']);
        $period = BookingPeriod::from($data['period']);
        $plan = PaymentPlan::from($data['payment_plan']);

        try {
            $quote = $pricing->quote(
                $roomType,
                $period,
                (int) $data['duration'],
                $plan,
                Carbon::parse($data['check_in'])->startOfDay(),
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        $rooms = $roomType->rooms()
            ->where('status', 'available')
            ->orderBy('name')
            ->get()
            ->filter(fn ($room) => $availability->isRoomAvailable($room, $quote->checkIn, $quote->checkOut))
            ->values();

        return response()->json([
            'check_in' => $quote->checkIn->toDateString(),
            'check_out' => $quote->checkOut->toDateString(),
            'duration' => $quote->duration,
            'unit_price' => $quote->unitPrice(),
            'total' => $quote->total,
            'unit' => $quote->periodLabel(),
            'rooms' => $rooms->map(fn ($room): array => [
                'id' => $room->id,
                'name' => $room->name,
            ])->all(),
        ]);
    }
}
