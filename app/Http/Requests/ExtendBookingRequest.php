<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExtendBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $routeValue = $this->route('booking');

        /** @var Booking|null $booking */
        $booking = $routeValue instanceof Booking
            ? $routeValue
            : Booking::query()->where('code', $routeValue)->first();

        return $this->user() instanceof User
            && $booking instanceof Booking
            && $this->user()->can('extend', $booking);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in(['daily', 'monthly', 'yearly'])],
            'duration' => ['required', 'integer', 'min:1', 'max:120'],
            'payment_plan' => ['required', Rule::in(['full', 'per_period', 'installment'])],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
