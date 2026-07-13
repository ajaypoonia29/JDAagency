<?php

namespace App\Models;

use App\Services\Finance\QuotationLedgerService;
use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Identity
        |--------------------------------------------------------------------------
        */

        'quotation_code',

        /*
        |--------------------------------------------------------------------------
        | Relationships
        |--------------------------------------------------------------------------
        */

        'lead_id',
        'customer_id',
        'meeting_id',
        'assigned_employee_id',

        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        'quotation_date',
        'valid_until',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',
        'approved_at',
        'approved_by',

        /*
        |--------------------------------------------------------------------------
        | Delivery Tracking
        |--------------------------------------------------------------------------
        */

        'quotation_sent_at',
        'quotation_sent_by',
        'quotation_send_count',
        'last_sent_to',

        /*
        |--------------------------------------------------------------------------
        | Financials
        |--------------------------------------------------------------------------
        */

        'subtotal',

        'discount_type',
        'discount_value',

        'tax_applicable',
        'tax_percentage',

        'tax',
        'grand_total',
        'total_paid',
        'balance_due',
        'payment_status',

        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        'customer_notes',
        'internal_notes',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'is_active',

        /*
        |--------------------------------------------------------------------------
        | Audit
        |--------------------------------------------------------------------------
        */

        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'quotation_date' => 'date',
        'valid_until' => 'date',

        'approved_at' => 'datetime',

        /*
        |--------------------------------------------------------------------------
        | Delivery Tracking
        |--------------------------------------------------------------------------
        */

        'quotation_sent_at' => 'datetime',

        /*
        |--------------------------------------------------------------------------
        | Financials
        |--------------------------------------------------------------------------
        */

        'subtotal' => 'decimal:2',

        'discount_type' => 'string',
        'discount_value' => 'decimal:2',

        'tax_applicable' => 'boolean',
        'tax_percentage' => 'decimal:2',

        'tax' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',

        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'quotation_sent_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation) {

            $quotation->total_paid = 0;
            $quotation->balance_due = $quotation->grand_total;
            $quotation->payment_status = 'Unpaid';

        });

        static::updated(function (Quotation $quotation): void {

            if ($quotation->wasChanged('grand_total')) {
                app(QuotationLedgerService::class)
                    ->recalculate($quotation);
            }

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function nextQuotationCode(): string
    {
        $lastQuotation = self::withTrashed()
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastQuotation && ! empty($lastQuotation->quotation_code)) {
            $nextNumber = ((int) substr($lastQuotation->quotation_code, -4)) + 1;
        }

        return 'QT-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}