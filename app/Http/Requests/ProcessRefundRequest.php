<?php

namespace App\Http\Requests;

use App\Models\Refund;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Refund $refund */
        $refund = $this->route('refund');

        return $this->user() instanceof User
            && $refund instanceof Refund
            && $this->user()->can('process', $refund);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }
}
