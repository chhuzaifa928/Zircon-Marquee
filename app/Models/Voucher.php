<?php

namespace App\Models;

use App\Services\NumberSequenceService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'voucher_no',
    'batch_uid',
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

    /** Voucher types: Receipt, Expense, Payment, Journal. */
    public const TYPES = ['RV', 'EV', 'PV', 'JV'];

    /** Human labels for the voucher types. */
    public const TYPE_LABELS = [
        'RV' => 'Receipt Voucher',
        'EV' => 'Expense Voucher',
        'PV' => 'Payment Voucher',
        'JV' => 'Journal Voucher',
    ];

    protected static function booted(): void
    {
        // Gapless, per-type voucher number (RV-0001, EV-0001, PV-0001) — §9.2.
        static::creating(function (Voucher $voucher) {
            if (blank($voucher->voucher_no) && filled($voucher->type)) {
                $voucher->voucher_no = app(NumberSequenceService::class)
                    ->next('voucher_no_'.$voucher->type, $voucher->type.'-');
            }
        });
    }

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
