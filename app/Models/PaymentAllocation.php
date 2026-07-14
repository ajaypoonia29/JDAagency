<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class PaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (PaymentAllocation $allocation): void {
            if ((float) $allocation->amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment allocation amount must be greater than zero.',
                ]);
            }
        });

        static::updating(function (PaymentAllocation $allocation): void {
            foreach (['payment_id', 'invoice_id'] as $attribute) {
                if ($allocation->isDirty($attribute)) {
                    $allocation->setAttribute(
                        $attribute,
                        $allocation->getOriginal($attribute),
                    );
                }
            }
        });
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
