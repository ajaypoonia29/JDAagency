<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('credited_total', 12, 2)
                ->default(0)
                ->after('grand_total');
            $table->decimal('refunded_total', 12, 2)
                ->default(0)
                ->after('credited_total');
            $table->decimal('net_total', 12, 2)
                ->default(0)
                ->after('refunded_total');

            $table->boolean('email_sent')
                ->default(false)
                ->after('invoice_generated_at');
            $table->timestamp('email_sent_at')
                ->nullable()
                ->after('email_sent');
            $table->foreignId('email_sent_by')
                ->nullable()
                ->after('email_sent_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedInteger('email_send_count')
                ->default(0)
                ->after('email_sent_by');
            $table->string('last_sent_to')
                ->nullable()
                ->after('email_send_count');
            $table->string('email_message_id')
                ->nullable()
                ->after('last_sent_to');
            $table->timestamp('last_delivery_attempt_at')
                ->nullable()
                ->after('email_message_id');
            $table->text('last_delivery_error')
                ->nullable()
                ->after('last_delivery_attempt_at');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['status', 'due_date']);
            $table->index('email_sent_at');
        });

        DB::table('invoices')->update([
            'net_total' => DB::raw('grand_total'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex(['status', 'due_date']);
            $table->dropIndex(['email_sent_at']);
            $table->dropConstrainedForeignId('email_sent_by');
            $table->dropColumn([
                'credited_total',
                'refunded_total',
                'net_total',
                'email_sent',
                'email_sent_at',
                'email_send_count',
                'last_sent_to',
                'email_message_id',
                'last_delivery_attempt_at',
                'last_delivery_error',
            ]);
        });
    }
};
