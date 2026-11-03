<?php

namespace App\Http\Requests\Admin;

use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RejectRefundRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
