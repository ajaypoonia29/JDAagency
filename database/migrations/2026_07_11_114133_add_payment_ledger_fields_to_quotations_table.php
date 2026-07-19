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
        Schema::table('quotations', function (Blueprint $table) {

            $table->decimal('total_paid', 12, 2)
                ->default(0)
                ->after('grand_total');

            $table->decimal('balance_due', 12, 2)
                ->default(0)
                ->after('total_paid');

            $table->enum('payment_status', [
                'Unpaid',
                'Partially Paid',
                'Paid',
            ])
            ->default('Unpaid')
            ->after('balance_due');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {

            $table->dropColumn([
                'total_paid',
                'balance_due',
                'payment_status',
            ]);

        });
    }
};