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
	'short_name',
	'sku',
	'category',

        /*
        |--------------------------------------------------------------------------
        | Service Information
        |--------------------------------------------------------------------------
        */

        'description',
        'standard_price',
	'pricing_type',
	'setup_fee',
	'monthly_price',
	'recurring',

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
	'display_order',
	'is_featured',
	'icon',
	'thumbnail',
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

        'standard_price'     => 'decimal:2',
	'setup_fee'          => 'decimal:2',
	'monthly_price'      => 'decimal:2',

	'gst_applicable'     => 'boolean',
	'gst_percentage'     => 'decimal:2',

	'recurring'          => 'boolean',
	'is_featured'        => 'boolean',

	'estimated_duration' => 'integer',
	'display_order'      => 'integer',

	'is_active'          => 'boolean',
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


	public function packageItems()
	{
    return $this->hasMany(PackageItem::class);
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