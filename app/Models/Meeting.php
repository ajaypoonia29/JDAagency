<?php

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasCreatedUpdatedBy;

    protected $fillable = [

        // Identity
        'meeting_code',

        // Relationships
        'lead_id',
        'customer_id',
        'assigned_employee_id',

        // Meeting
        'meeting_title',
        'meeting_type',
        'meeting_date',
        'meeting_time',
        'expected_duration',

        // Business Location
        'business_address',
        'latitude',
        'longitude',
        'google_maps_link',
        'business_photo',

        // Result
        'status',
        'outcome',
        'meeting_notes',

        // System
        'is_active',

        // Audit
        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'meeting_date' => 'date',

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

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
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

    public static function nextMeetingCode(): string
    {
        $next = static::withTrashed()->count() + 1;

        return sprintf('MET-%04d', $next);
    }
}