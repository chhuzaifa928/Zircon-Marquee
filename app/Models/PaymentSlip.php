<?php

namespace App\Models;

use App\Services\NumberSequenceService;
use App\Services\PostingService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'slip_no',
    'booking_id',
    'account_id',
    'date',
    'amount',
    'method',
    'reference',
    'remark',
    'voucher_id',
    'created_by',
])]
class PaymentSlip extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    /** Payment methods. */
    public const METHODS = ['cash', 'bank_transfer', 'cheque', 'card'];

    protected static function booted(): void
    {
        // Gapless, sequential slip number (SL-0001) — SRS §8.1.
        static::creating(function (PaymentSlip $slip) {
            if (blank($slip->slip_no)) {
                $slip->slip_no = app(NumberSequenceService::class)->next('slip_no', 'SL-');
            }
        });

        // Auto-post the Receipt Voucher (posting #1) on any entry path, and keep
        // it in step when a Super Admin edits or deletes a posted slip.
        static::created(fn (PaymentSlip $slip) => app(PostingService::class)->postReceipt($slip));

        static::updated(function (PaymentSlip $slip) {
            $posting = app(PostingService::class);
            $posting->reverseReceipt($slip);
            $posting->postReceipt($slip);
        });

        static::deleted(fn (PaymentSlip $slip) => app(PostingService::class)->reverseReceipt($slip));
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
