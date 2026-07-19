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
        Schema::create('company_profiles', function (Blueprint $table) {
    $table->id();

    // General Information
    $table->string('company_name');
    $table->string('company_tagline')->nullable();
    $table->string('owner_name')->nullable();

    // Branding
    $table->string('logo')->nullable();
    $table->string('favicon')->nullable();
    $table->string('primary_color')->default('#2563eb');
    $table->string('secondary_color')->default('#1e293b');

    // Contact
    $table->string('phone')->nullable();
    $table->string('whatsapp')->nullable();
    $table->string('email')->nullable();
    $table->string('support_email')->nullable();
    $table->string('website')->nullable();

    // Address
    $table->string('address_line_1')->nullable();
    $table->string('address_line_2')->nullable();
    $table->string('city')->nullable();
    $table->string('state')->nullable();
    $table->string('country')->nullable();
    $table->string('pincode')->nullable();

    // Social Media
    $table->string('facebook')->nullable();
    $table->string('instagram')->nullable();
    $table->string('linkedin')->nullable();
    $table->string('youtube')->nullable();
    $table->string('twitter')->nullable();

    // Invoice Settings
    $table->string('invoice_prefix')->default('INV');
    $table->string('receipt_prefix')->default('REC');
    $table->unsignedBigInteger('starting_invoice_number')->default(1001);
    $table->text('invoice_footer')->nullable();
    $table->text('receipt_footer')->nullable();

    // Currency
    $table->string('currency')->default('INR');
    $table->string('currency_symbol')->default('₹');
    $table->string('timezone')->default('Asia/Kolkata');
    $table->string('date_format')->default('d-m-Y');

    // Bank Details
    $table->string('bank_name')->nullable();
    $table->string('account_name')->nullable();
    $table->string('account_number')->nullable();
    $table->string('ifsc_code')->nullable();
    $table->string('upi_id')->nullable();
    $table->string('payment_qr')->nullable();

    // Integrations
    $table->string('google_drive_folder')->nullable();

    $table->string('smtp_host')->nullable();
    $table->string('smtp_port')->nullable();
    $table->string('smtp_username')->nullable();
    $table->text('smtp_password')->nullable();

    $table->text('whatsapp_api_key')->nullable();
    $table->string('whatsapp_phone_id')->nullable();


// Tax Details
$table->string('gst_number')->nullable();
$table->string('pan_number')->nullable();


    // Status
    $table->boolean('is_active')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
