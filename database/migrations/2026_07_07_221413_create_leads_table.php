<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Lead Identity
            |--------------------------------------------------------------------------
            */

            $table->string('lead_code')->unique();

            $table->enum('lead_status', [
                'New',
                'Contacted',
                'Qualified',
                'Proposal Sent',
                'Negotiation',
                'Won',
                'Lost',
            ])->default('New');

            $table->enum('priority', [
                'Low',
                'Medium',
                'High',
                'Urgent',
            ])->default('Medium');

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */

            $table->string('company_name')->nullable();
            $table->string('contact_person');

            $table->string('designation')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            $table->string('email');
            $table->string('phone');

            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Business
            |--------------------------------------------------------------------------
            */

            $table->string('industry')->nullable();
            $table->string('business_type')->nullable();
            $table->string('company_size')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Sales
            |--------------------------------------------------------------------------
            */

            $table->foreignId('assigned_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->string('lead_source')->nullable();

            $table->decimal('estimated_value', 15, 2)->default(0);

            $table->date('expected_closing_date')->nullable();
            $table->date('next_follow_up_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->longText('requirements_summary')->nullable();
            $table->longText('internal_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Conversion
            |--------------------------------------------------------------------------
            */

            $table->foreignId('converted_customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);

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

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};