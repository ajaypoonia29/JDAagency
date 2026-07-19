<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the foundational invoices table.
     *
     * Invoice workflow fields will be introduced incrementally through
     * subsequent migrations to preserve backward compatibility.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the foundational invoices table migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
