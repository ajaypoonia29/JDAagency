<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->text('business_address')
                ->nullable()
                ->after('shipping_pincode');

            $table->decimal('latitude', 10, 7)
                ->nullable()
                ->after('business_address');

            $table->decimal('longitude', 10, 7)
                ->nullable()
                ->after('latitude');

            $table->string('google_maps_link')
                ->nullable()
                ->after('longitude');

        });

        Schema::table('leads', function (Blueprint $table) {

            $table->text('business_address')
                ->nullable()
                ->after('company_size');

            $table->decimal('latitude', 10, 7)
                ->nullable()
                ->after('business_address');

            $table->decimal('longitude', 10, 7)
                ->nullable()
                ->after('latitude');

            $table->string('google_maps_link')
                ->nullable()
                ->after('longitude');

        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropColumn([
                'business_address',
                'latitude',
                'longitude',
                'google_maps_link',
            ]);

        });

        Schema::table('leads', function (Blueprint $table) {

            $table->dropColumn([
                'business_address',
                'latitude',
                'longitude',
                'google_maps_link',
            ]);

        });
    }
};