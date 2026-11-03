<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $booking_id
 * @property string $number
 * @property int $amount
 * @property int $late_fee
 * @property int $paid_amount
 * @property CarbonInterface $due_date
 * @property CarbonInterface $grace_until
 * @property CarbonInterface|null $late_fee_applied_at
 * @property CarbonInterface|null $upload_reset_at
 * @property bool $allow_partial
 * @property InvoiceStatus $status
 * @property-read int $total_due
 * @property-read int $remaining
 * @property Booking $booking
 */
#[Fillable([
    'booking_id', 'number', 'amount', 'late_fee', 'paid_amount', 'due_date',
    'grace_until', 'late_fee_applied_at', 'upload_reset_at', 'allow_partial', 'status',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $appends = ['total_due', 'remaining'];

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function totalDue(): int
    {
        return $this->amount + $this->late_fee;
    }

    public function remaining(): int
    {
        return max(0, $this->totalDue() - $this->paid_amount);
    }

    public function getTotalDueAttribute(): int
    {
        return $this->totalDue();
    }

    public function getRemainingAttribute(): int
    {
        return $this->remaining();
    }

    public function isPaid(): bool
    {
        return $this->paid_amount >= $this->totalDue();
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_date->isBefore(today());
    }

    public function isPastGrace(): bool
    {
        return $this->status->isOpen()
            && $this->grace_until->isBefore(today());
    }

    public function hasWaitingVerification(): bool
    {
        return $this->payments()
            ->where('status', PaymentStatus::WaitingVerification->value)
            ->exists();
    }

    public function rejectedUploadsCount(): int
    {
        return $this->payments()
            ->where('status', PaymentStatus::Rejected->value)
            ->when($this->upload_reset_at !== null, fn ($query) => $query->where('created_at', '>=', $this->upload_reset_at))
            ->count();
    }

    public function uploadIsLocked(): bool
    {
        return $this->rejectedUploadsCount() >= (int) config('booking.max_rejected_uploads');
    }

    public function recalculateStatus(): self
    {
        if ($this->status === InvoiceStatus::Cancelled) {
            return $this;
        }

        $status = match (true) {
            $this->paid_amount >= $this->totalDue() => InvoiceStatus::Paid,
            $this->paid_amount > 0 => InvoiceStatus::Partial,
            default => InvoiceStatus::Unpaid,
        };

        if ($this->status !== $status) {
            $this->status = $status;
            $this->save();
        }

        return $this;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'due_date' => 'date',
            'grace_until' => 'date',
            'late_fee_applied_at' => 'datetime',
            'upload_reset_at' => 'datetime',
            'amount' => 'integer',
            'late_fee' => 'integer',
            'paid_amount' => 'integer',
            'allow_partial' => 'boolean',
        ];
    }
}
