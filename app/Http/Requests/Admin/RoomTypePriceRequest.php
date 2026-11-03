<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingPeriod;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomTypePriceRequest extends FormRequest
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
        /** @var RoomType $roomType */
        $roomType = $this->route('roomType');

        return [
            'period' => ['required', Rule::enum(BookingPeriod::class)],
            'price' => ['required', 'integer', 'min:1000'],
            'min_duration' => ['required', 'integer', 'min:1'],
            'max_duration' => ['required', 'integer', 'min:1', 'gte:min_duration'],
            'is_active' => ['boolean'],
            'room_type_id' => ['sometimes'],
        ];
    }
}
