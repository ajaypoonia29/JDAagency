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
        Schema::create('services', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            $table->string('service_code')->unique();

            $table->string('service_name');

            $table->string('category');

            /*
            |--------------------------------------------------------------------------
            | Service Information
            |--------------------------------------------------------------------------
            */

            $table->longText('description')
                ->nullable();

            $table->decimal('standard_price', 12, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | GST
            |--------------------------------------------------------------------------
            */

            $table->boolean('gst_applicable')
                ->default(false);

            $table->decimal('gst_percentage', 5, 2)
                ->default(18);

            /*
            |--------------------------------------------------------------------------
            | Delivery
            |--------------------------------------------------------------------------
            */

            $table->integer('estimated_duration')
                ->default(0)
                ->comment('Duration in Days');

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};