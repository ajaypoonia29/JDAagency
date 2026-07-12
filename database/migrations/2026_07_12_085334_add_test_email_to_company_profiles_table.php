<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {

            $table->string('test_email')
                ->nullable()
                ->after('smtp_encryption');

        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {

            $table->dropColumn('test_email');

        });
    }
};