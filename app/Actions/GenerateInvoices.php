<?php

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Services\BookingSettings;
use App\Services\NumberGenerator;
use App\Services\PaymentPlan\PaymentPlanFactory;

class GenerateInvoices
{
    public function __construct(
        private readonly PaymentPlanFactory $plans,
        private readonly NumberGenerator $numbers,
        private readonly BookingSettings $settings,
    ) {}

    public function handle(Booking $booking): void
    {
        $plan = $this->plans->make($booking->payment_plan);

        foreach ($plan->invoices($booking) as $invoice) {
            $dueDate = $invoice['due_date'];

            $booking->invoices()->create([
                'number' => $this->numbers->next('INV'),
                'amount' => $invoice['amount'],
                'late_fee' => 0,
                'paid_amount' => 0,
                'due_date' => $dueDate,
                'grace_until' => $dueDate->copy()->addDays($this->settings->lateGraceDays()),
                'allow_partial' => $invoice['allow_partial'],
                'status' => InvoiceStatus::Unpaid,
            ]);
        }
    }
}
