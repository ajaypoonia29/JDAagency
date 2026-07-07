<?php

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [

        // Identity
        'lead_code',
        'lead_status',
        'priority',

        // Company
        'company_name',
        'contact_person',
        'designation',

        // Contact
        'email',
        'phone',
        'whatsapp',
        'website',

        // Business
        'industry',
        'business_type',
        'company_size',

        // Sales
        'assigned_employee_id',
        'lead_source',
        'estimated_value',
        'expected_closing_date',
        'next_follow_up_date',

        // Notes
        'requirements_summary',
        'internal_notes',

        // Conversion
        'converted_customer_id',

        // Status
        'is_active',

        // Audit
        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'estimated_value' => 'decimal:2',

        'expected_closing_date' => 'date',

        'next_follow_up_date' => 'date',

        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function convertedCustomer()
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function nextLeadCode(): string
    {
        return 'LEAD-' . str_pad(
            static::withTrashed()->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}