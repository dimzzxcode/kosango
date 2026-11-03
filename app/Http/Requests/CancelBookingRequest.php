<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
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
            && $this->user()->can('cancel', $booking);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:100'],
        ];
    }
}
