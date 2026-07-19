<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class QuotationItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relationships
        |--------------------------------------------------------------------------
        */

        'quotation_id',
        'service_id',

        /*
        |--------------------------------------------------------------------------
        | Item Information
        |--------------------------------------------------------------------------
        */

        'description',
        'quantity',
        'unit_price',
        'discount',
        'line_total',

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        'sort_order',
    ];

    protected $casts = [

        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        $assertMutable = function (QuotationItem $item): void {
            $quotationId = $item->quotation_id
                ?: $item->getOriginal('quotation_id');

            if (
                $quotationId
                && Quotation::query()
                    ->whereKey($quotationId)
                    ->whereHas('invoice')
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Quotation items cannot change after an invoice has been created.',
                ]);
            }
        };

        static::creating($assertMutable);
        static::updating($assertMutable);
        static::deleting($assertMutable);
        static::restoring($assertMutable);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}