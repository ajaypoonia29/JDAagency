<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->timestamp('quotation_sent_at')
                ->nullable()
                ->after('approved_by');

            $table->foreignId('quotation_sent_by')
                ->nullable()
                ->after('quotation_sent_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->unsignedInteger('quotation_send_count')
                ->default(0)
                ->after('quotation_sent_by');

            $table->string('last_sent_to')
                ->nullable()
                ->after('quotation_send_count');

        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->dropForeign([
                'quotation_sent_by',
            ]);

            $table->dropColumn([
                'quotation_sent_at',
                'quotation_sent_by',
                'quotation_send_count',
                'last_sent_to',
            ]);

        });
    }
};