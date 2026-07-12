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
        Schema::table('company_profiles', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Mail Configuration
            |--------------------------------------------------------------------------
            */

            $table->string('mail_mailer')
                ->default('smtp')
                ->after('smtp_password');

            $table->string('smtp_encryption')
                ->default('tls')
                ->after('mail_mailer');

            $table->string('mail_from_name')
                ->nullable()
                ->after('smtp_encryption');

            $table->string('mail_from_email')
                ->nullable()
                ->after('mail_from_name');

            $table->string('mail_reply_to')
                ->nullable()
                ->after('mail_from_email');

            /*
            |--------------------------------------------------------------------------
            | Future Communication Settings
            |--------------------------------------------------------------------------
            */

            $table->integer('smtp_timeout')
                ->default(30)
                ->after('mail_reply_to');

            $table->boolean('smtp_authentication')
                ->default(true)
                ->after('smtp_timeout');

            $table->boolean('queue_emails')
                ->default(false)
                ->after('smtp_authentication');

            $table->boolean('queue_whatsapp')
                ->default(false)
                ->after('queue_emails');

            $table->boolean('queue_sms')
                ->default(false)
                ->after('queue_whatsapp');

            $table->boolean('auto_send_receipts')
                ->default(true)
                ->after('queue_sms');

            $table->boolean('auto_send_statements')
                ->default(false)
                ->after('auto_send_receipts');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {

            $table->dropColumn([

                'mail_mailer',
                'smtp_encryption',
                'mail_from_name',
                'mail_from_email',
                'mail_reply_to',

                'smtp_timeout',
                'smtp_authentication',

                'queue_emails',
                'queue_whatsapp',
                'queue_sms',

                'auto_send_receipts',
                'auto_send_statements',

            ]);

        });
    }
};