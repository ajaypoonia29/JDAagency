<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Enterprise Pricing
            |--------------------------------------------------------------------------
            */

            $table->string('short_name')->nullable()->after('service_name');

            $table->string('sku')->nullable()->unique()->after('short_name');

            $table->enum('pricing_type', [
                'fixed',
                'hourly',
                'monthly',
                'custom',
            ])->default('fixed')->after('standard_price');

            $table->decimal('setup_fee', 12, 2)
                ->default(0)
                ->after('pricing_type');

            $table->decimal('monthly_price', 12, 2)
                ->default(0)
                ->after('setup_fee');

            $table->boolean('recurring')
                ->default(false)
                ->after('monthly_price');

            /*
            |--------------------------------------------------------------------------
            | Display
            |--------------------------------------------------------------------------
            */

            $table->integer('display_order')
                ->default(0)
                ->after('estimated_duration');

            $table->boolean('is_featured')
                ->default(false)
                ->after('display_order');

            $table->string('icon')
                ->nullable()
                ->after('is_featured');

            $table->string('thumbnail')
                ->nullable()
                ->after('icon');

            /*
            |--------------------------------------------------------------------------
            | Internal
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable()
                ->after('thumbnail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {

            $table->dropColumn([

                'short_name',
                'sku',
                'pricing_type',
                'setup_fee',
                'monthly_price',
                'recurring',
                'display_order',
                'is_featured',
                'icon',
                'thumbnail',
                'notes',

            ]);

        });
    }
};