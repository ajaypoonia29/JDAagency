<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
    | Core Relationships
    |--------------------------------------------------------------------------
    */

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'assigned_employee_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sales Workflow Relationships
    |--------------------------------------------------------------------------
    */

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Quotation::class,
            'meeting_id',
            'quotation_id',
            'id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sales Workflow Helpers
    |--------------------------------------------------------------------------
    */

    public function latestQuotation(): ?Quotation
    {
        return $this->quotations()
            ->latest('id')
            ->first();
    }

    public function hasQuotation(): bool
    {
        return $this->quotations()->exists();
    }

    public function totalPaymentsReceived(): float
    {
        return (float) $this->payments()->sum('amount');
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