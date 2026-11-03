<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $property_id
 * @property string $bank_name
 * @property string $account_number
 * @property string $account_holder
 * @property bool $is_active
 * @property Property|null $property
 */
#[Fillable(['property_id', 'bank_name', 'account_number', 'account_holder', 'is_active'])]
class BankAccount extends Model
{
    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
