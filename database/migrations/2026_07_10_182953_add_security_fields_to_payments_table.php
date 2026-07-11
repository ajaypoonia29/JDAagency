<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->uuid('receipt_uuid')
                ->nullable()
                ->unique()
                ->after('receipt_number');

            $table->string('verification_hash')
                ->nullable()
                ->after('receipt_uuid');

            $table->string('receipt_pdf')
                ->nullable()
                ->after('verification_hash');

            $table->timestamp('receipt_generated_at')
                ->nullable()
                ->after('receipt_pdf');

        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->dropColumn([

                'receipt_uuid',

                'verification_hash',

                'receipt_pdf',

                'receipt_generated_at',

            ]);

        });
    }
};