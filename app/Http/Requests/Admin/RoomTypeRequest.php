<?php

namespace App\Http\Requests\Admin;

use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomTypeRequest extends FormRequest
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
        /** @var RoomType|null $roomType */
        $roomType = $this->route('roomType');
        $propertyId = $roomType?->property_id ?? $this->input('property_id');

        return [
            'property_id' => [$roomType ? 'sometimes' : 'required', 'integer', Rule::exists('properties', 'id')->whereNull('deleted_at')],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('room_types', 'name')
                    ->where('property_id', $propertyId)
                    ->whereNull('deleted_at')
                    ->ignore($roomType?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
