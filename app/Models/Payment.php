<?php

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [

        'payment_no',

        'quotation_id',

        'customer_id',

        'amount',

        'payment_method',

        'transaction_reference',

        'payment_date',

        'notes',

        'receipt_generated',

        'receipt_number',

        'whatsapp_sent',

	'whatsapp_sent_at',

	'whatsapp_message_id',

	'email_sent',

	'email_sent_at',

	'email_message_id',

	'is_active',

	'receipt_uuid',

	'verification_hash',

	'receipt_pdf',

	'receipt_generated_at',
	
	'statement_pdf',

	'statement_generated_at',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'payment_date' => 'date',

        'receipt_generated' => 'boolean',

        'whatsapp_sent' => 'boolean',

	'whatsapp_sent_at' => 'datetime',

	'email_sent' => 'boolean',

	'email_sent_at' => 'datetime',

	'receipt_generated_at' => 'datetime',

	'statement_generated_at' => 'datetime',


	'is_active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }



public function completePayment(): void
{
    // Update the financial ledger for the quotation.
    $this->updateQuotationLedger();

    // Mark receipt information on the payment.
    $this->update([

        'receipt_generated' => true,

        'receipt_generated_at' => now(),

        'receipt_uuid' => $this->receipt_uuid
            ?: (string) Str::uuid(),

        'verification_hash' => $this->verification_hash
            ?: self::generateVerificationHash(),

    ]);

    // If the quotation has now been fully paid,
    // mark the workflow as Completed.
    if (
        $this->quotation &&
        $this->quotation->fresh()->payment_status === 'Paid'
    ) {
        $this->quotation->update([
            'status' => 'Completed',
        ]);
    }
}

public function updateQuotationLedger(): void
{
    if (! $this->quotation) {
        return;
    }

    $quotation = $this->quotation->fresh();

    $totalPaid = $quotation->payments()->sum('amount');

    $balance = max(
        $quotation->grand_total - $totalPaid,
        0
    );

    if ($totalPaid <= 0) {

        $status = 'Unpaid';

    } elseif ($balance > 0) {

        $status = 'Partially Paid';

    } else {

        $status = 'Paid';

    }

    $quotation->update([

        'total_paid' => $totalPaid,

        'balance_due' => $balance,

        'payment_status' => $status,

    ]);
}



    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function nextPaymentNumber(): string
    {
        $next = static::withTrashed()->count() + 1;

        return sprintf('PAY-%05d', $next);
    }

    public static function nextReceiptNumber(): string
    {
        $next = static::withTrashed()->count() + 1;

        return sprintf('RCT-%05d', $next);
}

public static function generateVerificationHash(): string
{
    return hash(
        'sha256',
        uniqid('', true) . now()->timestamp . random_int(100000, 999999)
    );
}

public function verificationUrl(): string
{
    return url('/verify/' . $this->verification_hash);
}
    
}