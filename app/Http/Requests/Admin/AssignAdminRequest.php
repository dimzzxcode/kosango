<?php

namespace App\Http\Requests\Admin;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAdminRequest extends FormRequest
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
        /** @var Property $property */
        $property = $this->route('property');

        return [
            'admin_ids' => ['array'],
            'admin_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('role', 'admin')->whereNull('deleted_at'),
            ],
        ];
    }
}
