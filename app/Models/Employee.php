<?php

namespace App\Models;

use App\Support\Helpers\FormHelper;
use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [

        // User
        'user_id',

        // Employee
        'employee_code',
        'full_name',
        'email',
        'phone',
        'alternate_phone',

        // Relationships
        'department_id',
        'designation_id',

        // Employment
        'joining_date',
        'employment_status',
        'salary',

        // Personal
        'date_of_birth',
        'gender',
        'blood_group',

        // Address
        'address',
        'city',
        'state',
        'country',
        'pincode',

        // Emergency Contact
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',

        // Documents
        'photo',
        'government_id',
        'aadhaar_number',
        'pan',
        'resume',
        'offer_letter',
        'google_drive_folder',

        // Notes
        'notes',

        // Status
        'is_active',

        // Audit
        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'joining_date'  => 'date',
        'date_of_birth' => 'date',
        'salary'        => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
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

    /**
     * Generate the next employee code.
     *
     * Example:
     * EMP-0001
     * EMP-0002
     * EMP-0003
     */
    public static function nextEmployeeCode(): string
    {
        $lastEmployee = self::orderByDesc('id')->first();

        $nextNumber = 1;

        if ($lastEmployee && ! empty($lastEmployee->employee_code)) {
            $nextNumber = ((int) substr($lastEmployee->employee_code, -4)) + 1;
        }

        return FormHelper::generateCode('EMP', $nextNumber);
    }
}