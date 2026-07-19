<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Refund extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [
        'refund_no',
        'invoice_id',
        'payment_id',
        'credit_note_id',
        'customer_id',
        'amount',
        'refund_method',
        'transaction_reference',
        'refund_date',
        'reason',
        'status',
        'processed_at',
        'processed_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'refund_uuid',
        'verification_hash',
        'refund_pdf',
        'refund_generated_at',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_date' => 'date',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refund_generated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (Refund $refund): void {
            foreach ([
                'refund_no',
                'invoice_id',
                'payment_id',
                'credit_note_id',
                'customer_id',
                'amount',
                'refund_method',
                'transaction_reference',
                'refund_date',
                'reason',
                'processed_at',
                'processed_by',
                'refund_uuid',
                'verification_hash',
                'refund_pdf',
                'refund_generated_at',
            ] as $attribute) {
                $original = $refund->getOriginal($attribute);

                if (filled($original) && $refund->isDirty($attribute)) {
                    $refund->setAttribute($attribute, $original);
                }
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public static function nextRefundNumber(): string
    {
        return sprintf(
            'RF-%05d',
            static::withTrashed()->count() + 1,
        );
    }

    public static function generateVerificationHash(): string
    {
        return hash('sha256', (string) Str::uuid() . microtime(true));
    }
}
