<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('credit_note_no')->unique();
            $table->foreignId('invoice_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();
            $table->date('issue_date');
            $table->string('status')->default('Draft');
            $table->string('currency', 3)->default('INR');
            $table->text('reason');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);

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

            $table->uuid('credit_note_uuid')->nullable()->unique();
            $table->string('verification_hash', 64)->nullable()->unique();
            $table->string('credit_note_pdf')->nullable();
            $table->timestamp('credit_note_generated_at')->nullable();

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
            $table->index(['customer_id', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
