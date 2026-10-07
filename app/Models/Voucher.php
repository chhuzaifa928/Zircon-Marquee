<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'voucher_no',
    'type',
    'date',
    'category',
    'remark',
    'debit_account_id',
    'credit_account_id',
    'amount',
    'source_type',
    'source_id',
    'created_by',
])]
class Voucher extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    /** Voucher types: Receipt, Expense, Payment. */
    public const TYPES = ['RV', 'EV', 'PV'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
