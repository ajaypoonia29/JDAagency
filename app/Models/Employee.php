<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [

        'user_id',

        'employee_code',

        'full_name',
        'email',
        'phone',
        'alternate_phone',

        'department',
        'designation',
        'joining_date',
        'employment_status',

        'address',
        'city',
        'state',
        'country',
        'pincode',

        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',

        'photo',
        'government_id',
        'pan',
        'resume',
        'offer_letter',

        'notes',

        'is_active',

        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'joining_date' => 'date',

        'is_active' => 'boolean',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}