<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $invoice_id
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property int $amount
 * @property int|null $bank_account_id
 * @property string|null $proof_path
 * @property string|null $proof_url
 * @property CarbonInterface|null $paid_at
 * @property int|null $verified_by
 * @property CarbonInterface|null $verified_at
 * @property string|null $rejection_reason
 * @property Invoice $invoice
 */
#[Fillable([
    'invoice_id', 'method', 'status', 'amount', 'bank_account_id', 'proof_path',
    'external_id', 'external_reference', 'payload', 'paid_at', 'verified_by',
    'verified_at', 'rejection_reason',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $appends = ['proof_url'];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function isWaitingVerification(): bool
    {
        return $this->status === PaymentStatus::WaitingVerification;
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function proofUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->proof_path
                ? route('payments.proof', $this)
                : null,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
