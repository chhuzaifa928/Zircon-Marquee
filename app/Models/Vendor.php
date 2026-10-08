<?php

namespace App\Models;

use App\Services\NumberSequenceService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'company',
    'phone',
    'email',
    'address',
    'account_id',
    'opening_balance',
    'notes',
])]
class Vendor extends Model
{
    protected static function booted(): void
    {
        // Each supplier gets a linked payable account (SRS §13.2). Codes run
        // from 2302 upward (2301 is the general suppliers payable head).
        static::creating(function (Vendor $vendor) {
            if ($vendor->account_id === null) {
                $n = app(NumberSequenceService::class)->nextNumber('vendor_account');

                $account = Account::create([
                    'code' => (string) (2301 + $n),
                    'name' => 'Supplier — '.$vendor->name,
                    'type' => 'supplier',
                    'opening_balance' => $vendor->opening_balance ?? 0,
                    'is_active' => true,
                ]);

                $vendor->account_id = $account->id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function eventCosts(): HasMany
    {
        return $this->hasMany(EventCost::class);
    }
}
