<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            $table->string('meeting_code')->unique();

            /*
            |--------------------------------------------------------------------------
            | Relationships
            |--------------------------------------------------------------------------
            */

            $table->foreignId('lead_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('assigned_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Meeting Information
            |--------------------------------------------------------------------------
            */

            $table->string('meeting_title');

            $table->enum('meeting_type', [
                'Office',
                'Client Site',
                'Online',
                'Phone',
            ])->default('Client Site');

            $table->date('meeting_date');

            $table->time('meeting_time');

            $table->integer('expected_duration')
                ->nullable()
                ->comment('Minutes');

            /*
            |--------------------------------------------------------------------------
            | Business Location
            |--------------------------------------------------------------------------
            */

            $table->text('business_address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('google_maps_link')->nullable();

            $table->string('business_photo')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Meeting Result
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'Scheduled',
                'Confirmed',
                'Completed',
                'Cancelled',
                'Rescheduled',
                'No Show',
            ])->default('Scheduled');

            $table->enum('outcome', [
                'Pending',
                'Interested',
                'Not Interested',
                'Follow-up Required',
                'Quotation Required',
                'Converted',
            ])->default('Pending');

            $table->longText('meeting_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | System
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);

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

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};