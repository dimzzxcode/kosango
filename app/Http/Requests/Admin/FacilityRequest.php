<?php

namespace App\Http\Requests\Admin;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityRequest extends FormRequest
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
        /** @var Facility|null $facility */
        $facility = $this->route('facility');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('facilities', 'name')->ignore($facility?->id),
            ],
            'icon' => ['nullable', 'string', 'max:100'],
        ];
    }
}
