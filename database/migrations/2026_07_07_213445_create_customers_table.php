<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Customer Identity
            |--------------------------------------------------------------------------
            */

            $table->string('customer_code')->unique();

            $table->enum('customer_type', [
                'Individual',
                'Business',
                'Government',
            ])->default('Business');

            $table->enum('customer_status', [
                'Lead',
                'Prospect',
                'Active',
                'Inactive',
                'Blacklisted',
            ])->default('Lead');

            /*
            |--------------------------------------------------------------------------
            | Company Information
            |--------------------------------------------------------------------------
            */

            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('display_name');

            $table->string('contact_person');
            $table->string('designation')->nullable();

            $table->string('industry')->nullable();
            $table->string('business_category')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Contact Information
            |--------------------------------------------------------------------------
            */

            $table->string('primary_email')->unique();
            $table->string('secondary_email')->nullable();

            $table->string('primary_phone');
            $table->string('alternate_phone')->nullable();

            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Tax Information
            |--------------------------------------------------------------------------
            */

            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('cin_number')->nullable();
            $table->string('tan_number')->nullable();
            $table->string('msme_number')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Billing Address
            |--------------------------------------------------------------------------
            */

            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_pincode')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Shipping Address
            |--------------------------------------------------------------------------
            */

            $table->boolean('same_as_billing')->default(true);

            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_pincode')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Social Links
            |--------------------------------------------------------------------------
            */

            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('twitter')->nullable();
            $table->string('youtube')->nullable();
            $table->string('google_business')->nullable();

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

            $table->decimal('credit_limit', 15, 2)->default(0);

            $table->string('payment_terms')->nullable();
            $table->string('currency')->default('INR');

            /*
            |--------------------------------------------------------------------------
            | Documents
            |--------------------------------------------------------------------------
            */

            $table->string('company_logo')->nullable();
            $table->string('gst_certificate')->nullable();
            $table->string('pan_document')->nullable();
            $table->string('business_license')->nullable();
            $table->string('agreement')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->longText('notes')->nullable();

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
        Schema::dropIfExists('customers');
    }
};