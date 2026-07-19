<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->boolean('tax_applicable')
                ->default(false)
                ->after('discount_value');

            $table->decimal('tax_percentage', 5, 2)
                ->default(18)
                ->after('tax_applicable');

        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->dropColumn([
                'tax_applicable',
                'tax_percentage',
            ]);

        });
    }
};