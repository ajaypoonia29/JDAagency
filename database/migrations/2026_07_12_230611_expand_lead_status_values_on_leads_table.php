<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('lead_status', [
                'New',
                'Contacted',
                'Qualified',
                'Meeting Scheduled',
                'Proposal Sent',
                'Negotiation',
                'Won',
                'Lost',
            ])
                ->default('New')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('leads')
            ->where('lead_status', 'Meeting Scheduled')
            ->update([
                'lead_status' => 'Qualified',
            ]);

        Schema::table('leads', function (Blueprint $table) {
            $table->enum('lead_status', [
                'New',
                'Contacted',
                'Qualified',
                'Proposal Sent',
                'Negotiation',
                'Won',
                'Lost',
            ])
                ->default('New')
                ->change();
        });
    }
};