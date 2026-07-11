<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->string('statement_pdf')
                ->nullable()
                ->after('receipt_pdf');

            $table->timestamp('statement_generated_at')
                ->nullable()
                ->after('statement_pdf');

        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->dropColumn([
                'statement_pdf',
                'statement_generated_at',
            ]);

        });
    }
};