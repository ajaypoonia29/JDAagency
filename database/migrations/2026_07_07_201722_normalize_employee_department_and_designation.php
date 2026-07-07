<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // Remove old string columns
            $table->dropColumn([
                'department',
                'designation',
            ]);

            // Add foreign keys
            $table->foreignId('department_id')
                ->nullable()
                ->after('alternate_phone')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('designation_id')
                ->nullable()
                ->after('department_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            $table->string('department')->nullable();
            $table->string('designation')->nullable();

            $table->dropConstrainedForeignId('designation_id');
            $table->dropConstrainedForeignId('department_id');
        });
    }
};