<?php

namespace App\Models;

use App\Enums\BookingPeriod;
use App\Enums\BookingStatus;
use App\Enums\PaymentPlan;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property int $property_id
 * @property int $room_type_id
 * @property int $room_id
 * @property int $user_id
 * @property int|null $extended_from_id
 * @property BookingPeriod $period
 * @property int $duration
 * @property PaymentPlan $payment_plan
 * @property int $unit_price
 * @property int $total_price
 * @property int $guests
 * @property CarbonInterface $check_in
 * @property CarbonInterface $check_out
 * @property BookingStatus $status
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $confirmed_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancel_reason
 * @property string|null $note
 * @property-read int $total_paid
 * @property Property $property
 * @property RoomType $roomType
 * @property Room $room
 * @property User $user
 */
#[Fillable([
    'code', 'property_id', 'room_type_id', 'room_id', 'user_id', 'extended_from_id',
    'period', 'duration', 'payment_plan', 'unit_price', 'total_price', 'guests',
    'check_in', 'check_out', 'status', 'expires_at', 'confirmed_at', 'completed_at',
    'cancelled_at', 'cancelled_by', 'cancel_reason', 'terms_accepted_at',
    'terms_version', 'note',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $appends = ['total_paid'];

    /**
     * Bookings are addressed by their public code in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'extended_from_id');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(self::class, 'extended_from_id');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('due_date');
    }

    /**
     * @return HasOne<Refund, $this>
     */
    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    /**
     * @return HasOne<Review, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function confirmedWithinRefundWindow(): bool
    {
        if ($this->confirmed_at === null) {
            return false;
        }

        return $this->confirmed_at
            ->addDays((int) config('booking.refund_window_days'))
            ->endOfDay()
            ->isFuture();
    }

    public function hasWaitingVerification(): bool
    {
        return $this->invoices()
            ->whereHas('payments', fn ($query) => $query->where('status', PaymentStatus::WaitingVerification->value))
            ->exists();
    }

    public function totalPaid(): int
    {
        if ($this->relationLoaded('invoices')) {
            return (int) $this->invoices->sum('paid_amount');
        }

        return (int) $this->invoices()->sum('paid_amount');
    }

    public function getTotalPaidAttribute(): int
    {
        return $this->totalPaid();
    }

    public function activeExtension(): ?self
    {
        return $this->extensions()
            ->whereIn('status', [BookingStatus::PendingPayment->value, BookingStatus::Confirmed->value])
            ->first();
    }

    public function canBeExtended(): bool
    {
        if ($this->status !== BookingStatus::Confirmed || $this->activeExtension() !== null) {
            return false;
        }

        $openAt = $this->check_out->copy()->subDays(
            (int) config('booking.extension_open_days_before_end'),
        );

        return now()->greaterThanOrEqualTo($openAt)
            && now()->lessThan($this->check_out->copy()->endOfDay());
    }

    public function canBeCompleted(): bool
    {
        return $this->status === BookingStatus::Confirmed
            && now()->greaterThanOrEqualTo($this->check_out->copy()->endOfDay())
            && ! $this->invoices()->whereIn('status', ['unpaid', 'partial'])->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period' => BookingPeriod::class,
            'payment_plan' => PaymentPlan::class,
            'status' => BookingStatus::class,
            'check_in' => 'date',
            'check_out' => 'date',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'duration' => 'integer',
            'unit_price' => 'integer',
            'total_price' => 'integer',
            'guests' => 'integer',
            'terms_version' => 'integer',
        ];
    }
}
