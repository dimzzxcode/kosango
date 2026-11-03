<?php

namespace App\Http\Requests\Admin;

use App\Enums\PropertyType;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
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
        /** @var Property|null $property */
        $property = $this->route('property');

        return [
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('properties', 'name')
                    ->where('city_id', $this->input('city_id'))
                    ->whereNull('deleted_at')
                    ->ignore($property?->id),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'facilities' => ['array'],
            'facilities.*' => ['integer', Rule::exists('facilities', 'id')],
            'thumbnail' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'thumbnail_alt' => ['nullable', 'string', 'max:150'],
            'gallery' => ['array', 'max:'.config('booking.max_gallery_images')],
            'gallery.*' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }
}
