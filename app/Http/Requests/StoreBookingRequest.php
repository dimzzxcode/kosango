<?php

namespace App\Http\Requests;

use App\Enums\BookingPeriod;
use App\Enums\PaymentPlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isCustomer();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'integer', Rule::exists('room_types', 'id')->whereNull('deleted_at')],
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->whereNull('deleted_at')],
            'period' => ['required', Rule::enum(BookingPeriod::class)],
            'duration' => ['required', 'integer', 'min:1', 'max:120'],
            'payment_plan' => ['required', Rule::enum(PaymentPlan::class)],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'note' => ['nullable', 'string', 'max:500'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_in.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka dan simbol telepon.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null) {
                return;
            }

            if ($user->email_verified_at === null) {
                $validator->errors()->add('booking', 'Verifikasi email terlebih dahulu sebelum memesan.');
            }

            if (blank($this->input('phone') ?? $user->phone)) {
                $validator->errors()->add('phone', 'Nomor telepon wajib diisi sebelum memesan.');
            }

            $roomType = RoomType::query()->with('property')->find($this->input('room_type_id'));
            $room = Room::query()->find($this->input('room_id'));

            if ($roomType === null || $room === null) {
                return;
            }

            if ((int) $this->input('guests') > $roomType->capacity) {
                $validator->errors()->add(
                    'guests',
                    "Jumlah tamu melebihi kapasitas tipe kamar (maksimal {$roomType->capacity}).",
                );
            }

            if ($room->room_type_id !== $roomType->id || $room->property_id !== $roomType->property_id) {
                $validator->errors()->add('room_id', 'Kamar yang dipilih tidak sesuai dengan tipe kamar.');
            }
        });
    }
}
