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

class CreditNote extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [
        'credit_note_no',
        'invoice_id',
        'customer_id',
        'issue_date',
        'status',
        'currency',
        'reason',
        'subtotal',
        'tax',
        'grand_total',
        'issued_at',
        'issued_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'credit_note_uuid',
        'verification_hash',
        'credit_note_pdf',
        'credit_note_generated_at',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
        'credit_note_generated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (CreditNote $creditNote): void {
            $immutable = [
                'credit_note_no',
                'invoice_id',
                'customer_id',
                'credit_note_uuid',
                'verification_hash',
                'credit_note_pdf',
                'credit_note_generated_at',
                'issued_at',
                'issued_by',
            ];

            if (filled($creditNote->getOriginal('issued_at'))) {
                $immutable = array_merge($immutable, [
                    'issue_date',
                    'currency',
                    'reason',
                    'subtotal',
                    'tax',
                    'grand_total',
                ]);
            }

            foreach ($immutable as $attribute) {
                $original = $creditNote->getOriginal($attribute);

                if (filled($original) && $creditNote->isDirty($attribute)) {
                    $creditNote->setAttribute($attribute, $original);
                }
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class)->orderBy('sort_order');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public static function nextCreditNoteNumber(): string
    {
        return sprintf(
            'CN-%05d',
            static::withTrashed()->count() + 1,
        );
    }

    public static function generateVerificationHash(): string
    {
        return hash('sha256', (string) Str::uuid() . microtime(true));
    }
}
