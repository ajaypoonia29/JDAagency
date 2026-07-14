<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [
        'invoice_no',
        'quotation_id',
        'customer_id',
        'lead_id',
        'invoice_date',
        'due_date',
        'status',
        'currency',
        'subtotal',
        'discount_type',
        'discount_value',
        'tax',
        'grand_total',
        'total_paid',
        'balance_due',
        'customer_notes',
        'internal_notes',
        'issued_at',
        'issued_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'invoice_uuid',
        'verification_hash',
        'invoice_pdf',
        'invoice_generated_at',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
        'invoice_generated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (Invoice $invoice): void {
            $immutable = [
                'invoice_no',
                'quotation_id',
                'customer_id',
                'lead_id',
                'invoice_uuid',
                'verification_hash',
                'invoice_pdf',
                'invoice_generated_at',
                'issued_at',
                'issued_by',
            ];

            if (filled($invoice->getOriginal('issued_at'))) {
                $immutable = array_merge($immutable, [
                    'invoice_date',
                    'due_date',
                    'currency',
                    'subtotal',
                    'discount_type',
                    'discount_value',
                    'tax',
                    'grand_total',
                ]);
            }

            foreach ($immutable as $attribute) {
                $original = $invoice->getOriginal($attribute);

                if (filled($original) && $invoice->isDirty($attribute)) {
                    $invoice->setAttribute($attribute, $original);
                }
            }
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function nextInvoiceNumber(): string
    {
        $next = static::withTrashed()->count() + 1;

        return sprintf('INV-%05d', $next);
    }

    public static function generateVerificationHash(): string
    {
        return hash('sha256', (string) Str::uuid() . microtime(true));
    }
}
