<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['code', 'name', 'type', 'opening_balance', 'is_active'])]
class Account extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    /** Chart-of-accounts types. */
    public const TYPES = ['cash', 'bank', 'staff', 'asset', 'liability', 'equity', 'income', 'expense', 'supplier'];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function debitVouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'debit_account_id');
    }

    public function creditVouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'credit_account_id');
    }

    public function paymentSlips(): HasMany
    {
        return $this->hasMany(PaymentSlip::class);
    }
}
