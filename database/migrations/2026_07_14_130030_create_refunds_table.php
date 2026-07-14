<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->string('refund_no')->unique();
            $table->foreignId('invoice_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('payment_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('credit_note_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('refund_method');
            $table->string('transaction_reference')->nullable();
            $table->date('refund_date');
            $table->text('reason');
            $table->string('status')->default('Processed');

            $table->timestamp('processed_at');
            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('cancel_reason')->nullable();

            $table->uuid('refund_uuid')->nullable()->unique();
            $table->string('verification_hash', 64)->nullable()->unique();
            $table->string('refund_pdf')->nullable();
            $table->timestamp('refund_generated_at')->nullable();

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
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['payment_id', 'status']);
            $table->index(['credit_note_id', 'status']);
            $table->index(['customer_id', 'refund_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
