<?php

namespace App\Models;

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
