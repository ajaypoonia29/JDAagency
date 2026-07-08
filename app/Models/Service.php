<?php

namespace App\Models;

use App\Traits\HasCreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
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

        'service_code',
        'service_name',
        'category',

        /*
        |--------------------------------------------------------------------------
        | Service Information
        |--------------------------------------------------------------------------
        */

        'description',
        'standard_price',

        /*
        |--------------------------------------------------------------------------
        | GST
        |--------------------------------------------------------------------------
        */

        'gst_applicable',
        'gst_percentage',

        /*
        |--------------------------------------------------------------------------
        | Delivery
        |--------------------------------------------------------------------------
        */

        'estimated_duration',

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

        'standard_price'      => 'decimal:2',
        'gst_applicable'      => 'boolean',
        'gst_percentage'      => 'decimal:2',
        'estimated_duration'  => 'integer',
        'is_active'           => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function quotationItems()
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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function nextServiceCode(): string
    {
        $lastService = self::withTrashed()
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastService && ! empty($lastService->service_code)) {
            $nextNumber = ((int) substr($lastService->service_code, -4)) + 1;
        }

        return 'SER-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}