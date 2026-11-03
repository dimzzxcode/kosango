<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
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
        /** @var Room|null $room */
        $room = $this->route('room');
        $propertyId = $room?->property_id ?? $this->input('property_id');

        return [
            'property_id' => [$room ? 'sometimes' : 'required', 'integer', Rule::exists('properties', 'id')->whereNull('deleted_at')],
            'room_type_id' => ['required', 'integer', Rule::exists('room_types', 'id')->whereNull('deleted_at')],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'name')
                    ->where('property_id', $propertyId)
                    ->whereNull('deleted_at')
                    ->ignore($room?->id),
            ],
            'status' => ['sometimes', Rule::enum(RoomStatus::class)],
        ];
    }
}
