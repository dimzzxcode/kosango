<?php

namespace App\Http\Requests;

use App\Models\Refund;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitRefundAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Refund $refund */
        $refund = $this->route('refund');

        return $this->user() instanceof User
            && $refund instanceof Refund
            && $this->user()->can('submitAccount', $refund);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder' => ['required', 'string', 'max:100'],
        ];
    }
}
