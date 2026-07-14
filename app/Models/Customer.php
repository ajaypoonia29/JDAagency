<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Helpers\FormHelper;
use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Customer Identity
        |--------------------------------------------------------------------------
        */

        'customer_code',
        'customer_type',
        'customer_status',

        /*
        |--------------------------------------------------------------------------
        | Company Information
        |--------------------------------------------------------------------------
        */

        'company_name',
        'legal_name',
        'display_name',
        'contact_person',
        'designation',
        'industry',
        'business_category',

        /*
        |--------------------------------------------------------------------------
        | Contact Information
        |--------------------------------------------------------------------------
        */

        'primary_email',
        'secondary_email',
        'primary_phone',
        'alternate_phone',
        'whatsapp',
        'website',

        /*
        |--------------------------------------------------------------------------
        | Tax Information
        |--------------------------------------------------------------------------
        */

        'gst_number',
        'pan_number',
        'cin_number',
        'tan_number',
        'msme_number',

        /*
        |--------------------------------------------------------------------------
        | Billing Address
        |--------------------------------------------------------------------------
        */

        'billing_address',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_pincode',

        /*
        |--------------------------------------------------------------------------
        | Shipping Address
        |--------------------------------------------------------------------------
        */

        'same_as_billing',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_pincode',

        /*
        |--------------------------------------------------------------------------
        | Business Location
        |--------------------------------------------------------------------------
        */

        'business_address',
        'latitude',
        'longitude',
        'google_maps_link',

        /*
        |--------------------------------------------------------------------------
        | Social Media
        |--------------------------------------------------------------------------
        */

        'facebook',
        'instagram',
        'linkedin',
        'twitter',
        'youtube',
        'google_business',

        /*
        |--------------------------------------------------------------------------
        | Sales
        |--------------------------------------------------------------------------
        */

        'assigned_employee_id',
        'lead_source',
        'credit_limit',
        'payment_terms',
        'currency',

        /*
        |--------------------------------------------------------------------------
        | Documents
        |--------------------------------------------------------------------------
        */

        'company_logo',
        'gst_certificate',
        'pan_document',
        'business_license',
        'agreement',

        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        'notes',

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

        'same_as_billing' => 'boolean',
        'credit_limit'    => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function convertedLeads(): HasMany
    {
        return $this->hasMany(
            Lead::class,
            'converted_customer_id',
        );
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Generate next customer code.
     *
     * Example:
     * CUS-0001
     */
    public static function nextCustomerCode(): string
    {
        $lastCustomer = self::orderByDesc('id')->first();

        $nextNumber = 1;

        if ($lastCustomer && ! empty($lastCustomer->customer_code)) {
            $nextNumber = ((int) substr($lastCustomer->customer_code, -4)) + 1;
        }

        return FormHelper::generateCode('CUS', $nextNumber);
    }
}