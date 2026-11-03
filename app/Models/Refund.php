<?php

namespace App\Models;

use App\Enums\RefundInitiator;
use App\Enums\RefundStatus;
use Carbon\CarbonInterface;
use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $booking_id
 * @property int|null $requested_by
 * @property RefundInitiator $initiated_by
 * @property int $amount
 * @property RefundStatus $status
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property string|null $account_holder
 * @property string|null $proof_path
 * @property string|null $proof_url
 * @property int|null $processed_by
 * @property CarbonInterface|null $processed_at
 * @property string|null $rejection_reason
 * @property Booking $booking
 */
#[Fillable([
    'booking_id', 'requested_by', 'initiated_by', 'amount', 'status', 'bank_name',
    'account_number', 'account_holder', 'proof_path', 'processed_by',
    'processed_at', 'rejection_reason', 'note',
])]
class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = ['proof_url'];

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function proofUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->proof_path
                ? route('refunds.proof', $this)
                : null,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initiated_by' => RefundInitiator::class,
            'status' => RefundStatus::class,
            'amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }
}
