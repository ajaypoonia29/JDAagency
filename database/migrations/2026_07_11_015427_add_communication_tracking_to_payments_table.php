<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_sent');

            $table->timestamp('email_sent_at')->nullable()->after('email_sent');

            $table->string('whatsapp_message_id')->nullable()->after('whatsapp_sent_at');

            $table->string('email_message_id')->nullable()->after('email_sent_at');

        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->dropColumn([
                'whatsapp_sent_at',
                'email_sent_at',
                'whatsapp_message_id',
                'email_message_id',
            ]);

        });
    }
};