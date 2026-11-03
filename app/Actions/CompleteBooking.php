<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\Notifier;
use App\Services\StatusHistoryRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteBooking
{
    public function __construct(
        private readonly StatusHistoryRecorder $histories,
        private readonly Notifier $notifier,
    ) {}

    public function handle(Booking $booking, User $actor, ?string $note = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $note): Booking {
            $booking = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            $booking->loadMissing('invoices');

            if (! $actor->canManageProperty($booking->property)) {
                throw ValidationException::withMessages(['booking' => 'Anda tidak berhak menyelesaikan booking ini.']);
            }

            if ($booking->status !== BookingStatus::Confirmed) {
                throw ValidationException::withMessages(['booking' => 'Hanya booking terkonfirmasi yang dapat diselesaikan.']);
            }

            if (today()->lessThan($booking->check_out)) {
                throw ValidationException::withMessages(['booking' => 'Booking belum melewati tanggal check-out.']);
            }

            $hasOutstanding = $booking->invoices->contains(
                fn ($invoice) => $invoice->status->isOpen(),
            );

            if ($hasOutstanding) {
                if (! $actor->isSuperadmin()) {
                    throw ValidationException::withMessages([
                        'booking' => 'Masih ada tagihan belum lunas. Hanya superadmin yang dapat menutup dengan catatan.',
                    ]);
                }

                if (blank($note)) {
                    throw ValidationException::withMessages(['note' => 'Catatan wajib diisi saat menutup booking bertunggakan.']);
                }
            }

            $booking->update([
                'status' => BookingStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->histories->record(
                $booking,
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
                $actor,
                $note,
            );

            $this->notifier->toUser(
                $booking->user,
                'Booking selesai',
                "Booking {$booking->code} telah ditandai selesai. Terima kasih telah menginap.",
                route('bookings.show', $booking),
            );

            return $booking->refresh()->load(['property.city', 'roomType', 'room', 'invoices']);
        });
    }
}
