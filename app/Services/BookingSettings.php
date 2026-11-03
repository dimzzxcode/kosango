<?php

namespace App\Services;

use App\Enums\BookingPeriod;

class BookingSettings
{
    public function minDownPaymentPercent(): int
    {
        return (int) config('booking.min_down_payment_percent');
    }

    public function lateGraceDays(): int
    {
        return (int) config('booking.late_grace_days');
    }

    public function lateFee(): int
    {
        return (int) config('booking.late_fee');
    }

    public function refundWindowDays(): int
    {
        return (int) config('booking.refund_window_days');
    }

    public function paymentExpiryHours(): int
    {
        return (int) config('booking.payment_expiry_hours');
    }

    public function maxRejectedUploads(): int
    {
        return (int) config('booking.max_rejected_uploads');
    }

    public function extensionOpenDaysBeforeEnd(): int
    {
        return (int) config('booking.extension_open_days_before_end');
    }

    public function maxGalleryImages(): int
    {
        return (int) config('booking.max_gallery_images');
    }

    public function termsVersion(): int
    {
        return (int) config('booking.terms_version');
    }

    public function invoiceReminderDays(): int
    {
        return (int) config('booking.invoice_reminder_days');
    }

    public function maxDuration(BookingPeriod $period): int
    {
        return (int) config("booking.default_max_duration.{$period->value}", $period->defaultMaxDuration());
    }

    public function paymentDriver(): string
    {
        return (string) config('booking.payment_driver');
    }
}
