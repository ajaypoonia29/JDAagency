<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            $table->timestamp('approved_at')
                ->nullable()
                ->after('status');

            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->dropForeign(['approved_by']);

            $table->dropColumn([
                'approved_at',
                'approved_by',
            ]);

        });
    }
};