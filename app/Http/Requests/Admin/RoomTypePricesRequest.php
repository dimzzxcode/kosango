<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingPeriod;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomTypePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isSuperadmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.period' => ['required', Rule::enum(BookingPeriod::class), 'distinct'],
            'prices.*.price' => ['required', 'integer', 'min:1000'],
            'prices.*.min_duration' => ['required', 'integer', 'min:1', 'max:60'],
            'prices.*.max_duration' => ['required', 'integer', 'min:1', 'max:60', 'gte:prices.*.min_duration'],
            'prices.*.is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prices.*.period.distinct' => 'Setiap periode hanya boleh muncul satu kali.',
            'prices.*.price.min' => 'Harga minimal Rp 1.000.',
            'prices.*.max_duration.gte' => 'Durasi maksimal harus lebih besar atau sama dengan durasi minimal.',
        ];
    }
}
