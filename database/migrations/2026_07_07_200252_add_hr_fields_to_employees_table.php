<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            // Personal Information
            $table->date('date_of_birth')->nullable()->after('joining_date');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('blood_group')->nullable()->after('gender');

            // Employment
            $table->decimal('salary', 12, 2)->nullable()->after('employment_status');

            // Documents
            $table->string('aadhaar_number')->nullable()->after('pan');
            $table->string('google_drive_folder')->nullable()->after('offer_letter');

            // Soft Delete
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {

            $table->dropColumn([
                'date_of_birth',
                'gender',
                'blood_group',
                'salary',
                'aadhaar_number',
                'google_drive_folder',
            ]);

            $table->dropSoftDeletes();
        });
    }
};