<?php

namespace App\Actions;

use App\Enums\BookingPeriod;
use App\Enums\BookingStatus;
use App\Enums\PaymentPlan;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\Notifier;
use App\Services\NumberGenerator;
use App\Services\Pricing\PricingService;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExtendBooking
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
     * @param  array{period: string, duration: int, payment_plan: string, guests: int, note?: string|null}  $data
     */
    public function handle(Booking $parent, User $user, array $data): Booking
    {
        return DB::transaction(function () use ($parent, $user, $data): Booking {
            $parent = Booking::query()->whereKey($parent->getKey())->lockForUpdate()->firstOrFail();

            if ($parent->user_id !== $user->id) {
                throw ValidationException::withMessages(['booking' => 'Booking bukan milik Anda.']);
            }

            if (! $parent->canBeExtended()) {
                throw ValidationException::withMessages([
                    'booking' => 'Perpanjangan hanya dapat dilakukan pada booking terkonfirmasi, mulai H-14 sebelum masa sewa berakhir.',
                ]);
            }

            $room = Room::query()->whereKey($parent->room_id)->lockForUpdate()->firstOrFail();

            if ($room->status !== RoomStatus::Available) {
                throw ValidationException::withMessages(['room' => 'Kamar sedang dalam perawatan.']);
            }

            $period = BookingPeriod::from($data['period']);
            $plan = PaymentPlan::from($data['payment_plan']);
            $checkIn = $parent->check_out->copy()->startOfDay();

            if ((int) $data['guests'] > $parent->roomType->capacity) {
                throw ValidationException::withMessages([
                    'guests' => "Jumlah tamu melebihi kapasitas tipe kamar (maksimal {$parent->roomType->capacity}).",
                ]);
            }

            $quote = $this->pricing->quote(
                $parent->roomType,
                $period,
                (int) $data['duration'],
                $plan,
                $checkIn,
            );

            if (! $this->availability->isRoomAvailable($room, $quote->checkIn, $quote->checkOut)) {
                throw ValidationException::withMessages([
                    'room' => 'Kamar sudah dipesan pihak lain pada tanggal perpanjangan.',
                ]);
            }

            $extension = Booking::query()->create([
                'code' => $this->numbers->next('BK'),
                'property_id' => $parent->property_id,
                'room_type_id' => $parent->room_type_id,
                'room_id' => $parent->room_id,
                'user_id' => $user->id,
                'extended_from_id' => $parent->id,
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

            $this->generateInvoices->handle($extension);

            $this->histories->record(
                $extension,
                null,
                BookingStatus::PendingPayment->value,
                $user,
                "Perpanjangan dari booking {$parent->code}.",
            );

            $this->notifier->toPropertyAdmins(
                $parent->property,
                'Perpanjangan booking',
                "Perpanjangan {$extension->code} dibuat dari booking {$parent->code}.",
                route('admin.bookings.show', $extension),
            );

            return $extension->load(['property.city', 'roomType', 'room', 'invoices']);
        });
    }
}
