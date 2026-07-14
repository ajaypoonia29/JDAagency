<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('invoice_no')->nullable()->unique();

            $table->foreignId('quotation_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();

            $table->string('status')->default('Draft');
            $table->string('currency', 3)->default('INR');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);

            $table->longText('customer_notes')->nullable();
            $table->longText('internal_notes')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('void_reason')->nullable();

            $table->uuid('invoice_uuid')->nullable()->unique();
            $table->string('verification_hash', 64)->nullable()->unique();
            $table->string('invoice_pdf')->nullable();
            $table->timestamp('invoice_generated_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['quotation_id']);
            $table->dropUnique(['invoice_no']);
            $table->dropUnique(['invoice_uuid']);
            $table->dropUnique(['verification_hash']);

            $table->dropConstrainedForeignId('quotation_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('lead_id');
            $table->dropConstrainedForeignId('issued_by');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');

            $table->dropColumn([
                'invoice_no',
                'invoice_date',
                'due_date',
                'status',
                'currency',
                'subtotal',
                'discount_type',
                'discount_value',
                'tax',
                'grand_total',
                'total_paid',
                'balance_due',
                'customer_notes',
                'internal_notes',
                'issued_at',
                'voided_at',
                'void_reason',
                'invoice_uuid',
                'verification_hash',
                'invoice_pdf',
                'invoice_generated_at',
                'is_active',
                'deleted_at',
            ]);
        });
    }
};
