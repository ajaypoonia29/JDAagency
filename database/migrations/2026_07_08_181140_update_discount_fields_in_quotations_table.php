<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->dropColumn('discount');

            $table->enum('discount_type', [
                'fixed',
                'percentage',
            ])->default('fixed');

            $table->decimal('discount_value', 12, 2)
                ->default(0);

        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->dropColumn([
                'discount_type',
                'discount_value',
            ]);

            $table->decimal('discount', 12, 2)
                ->default(0);

        });
    }
};