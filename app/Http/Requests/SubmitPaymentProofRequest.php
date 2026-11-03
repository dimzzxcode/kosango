<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return $this->user() instanceof User
            && $invoice instanceof Invoice
            && $this->user()->can('uploadProof', $invoice);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'bank_account_id' => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where('is_active', true)],
            'proof' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal transfer wajib diisi.',
            'proof.required' => 'Pilih file bukti transfer terlebih dahulu.',
            'proof.mimes' => 'Bukti transfer harus berupa PNG, JPG, JPEG, atau WEBP.',
            'proof.max' => 'Ukuran bukti transfer maksimal 2 MB.',
        ];
    }
}
