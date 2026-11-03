<?php

namespace App\Actions;

use App\Enums\BookingPeriod;
use App\Enums\BookingStatus;
use App\Enums\PaymentPlan;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\Notifier;
use App\Services\NumberGenerator;
use App\Services\Pricing\PricingService;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBooking
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly AvailabilityService $availability,
        private readonly NumberGenerator $numbers,
        private readonly GenerateInvoices $generateInvoices,
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @param  array{room_type_id: int, room_id: int, check_in: string, duration: int, period: string, payment_plan: string, guests: int, phone?: string|null, note?: string|null}  $data
     */
    public function handle(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data): Booking {
            $period = BookingPeriod::from($data['period']);
            $plan = PaymentPlan::from($data['payment_plan']);
            $checkIn = Carbon::parse($data['check_in'])->startOfDay();

            $roomType = RoomType::query()->findOrFail($data['room_type_id']);
            $property = $roomType->property;

            if (! $property->isPublished()) {
                throw ValidationException::withMessages([
                    'room_type_id' => 'Properti ini belum dipublikasikan.',
                ]);
            }

            $room = Room::query()
                ->whereKey($data['room_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($room->property_id !== $property->id || $room->room_type_id !== $roomType->id) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar yang dipilih tidak sesuai dengan properti dan tipe kamar.',
                ]);
            }

            if ($room->status !== RoomStatus::Available) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar yang dipilih sedang tidak tersedia.',
                ]);
            }

            if ((int) $data['guests'] > $roomType->capacity) {
                throw ValidationException::withMessages([
                    'guests' => "Jumlah tamu melebihi kapasitas tipe kamar (maksimal {$roomType->capacity}).",
                ]);
            }

            $quote = $this->pricing->quote($roomType, $period, (int) $data['duration'], $plan, $checkIn);

            if (! $this->availability->isRoomAvailable($room, $quote->checkIn, $quote->checkOut)) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar sudah dipesan pada tanggal tersebut. Silakan pilih kamar lain.',
                ]);
            }

            if (filled($data['phone'] ?? null)) {
                $user->forceFill(['phone' => $data['phone']])->save();
            }

            $booking = Booking::query()->create([
                'code' => $this->numbers->next('BK'),
                'property_id' => $property->id,
                'room_type_id' => $roomType->id,
                'room_id' => $room->id,
                'user_id' => $user->id,
                'period' => $period,
                'duration' => $quote->duration,
                'payment_plan' => $plan,
                'unit_price' => $quote->unitPrice(),
                'total_price' => $quote->total,
                'guests' => (int) $data['guests'],
                'check_in' => $quote->checkIn,
                'check_out' => $quote->checkOut,
                'status' => BookingStatus::PendingPayment,
                'expires_at' => now()->addHours((int) config('booking.payment_expiry_hours')),
                'terms_accepted_at' => now(),
                'terms_version' => (int) config('booking.terms_version'),
                'note' => $data['note'] ?? null,
            ]);

            $this->generateInvoices->handle($booking);

            $this->histories->record($booking, null, BookingStatus::PendingPayment->value, $user, 'Booking dibuat.');

            $this->notifier->toPropertyAdmins(
                $property,
                'Booking baru '.$booking->code,
                "Booking baru untuk {$property->name} oleh {$user->name}.",
                route('admin.bookings.show', $booking),
            );

            return $booking->load(['property.city', 'roomType', 'room', 'invoices']);
        });
    }
}
