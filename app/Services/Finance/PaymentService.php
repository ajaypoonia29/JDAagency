<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Documents\DocumentService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    public function __construct(
        private readonly DocumentService $documents,
    ) {
    }

    /**
     * Create and complete a payment as one database workflow.
     *
     * If receipt generation or the database commit fails, the payment and
     * ledger changes are rolled back and any generated receipt file is removed.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Payment
    {
        $validated = Validator::make($data, [
            'quotation_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => [
                'required',
                'string',
                'in:Cash,UPI,Bank Transfer,Cheque,Credit Card,Debit Card',
            ],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ])->validate();

        $receiptPath = null;
        $receiptNumber = null;
        $receiptGenerationStarted = false;

        try {
            return DB::transaction(function () use (
                $validated,
                &$receiptPath,
                &$receiptNumber,
                &$receiptGenerationStarted,
            ): Payment {
                $quotation = Quotation::query()
                    ->lockForUpdate()
                    ->find($validated['quotation_id']);

                if (! $quotation) {
                    throw ValidationException::withMessages([
                        'quotation_id' => 'The selected quotation is unavailable.',
                    ]);
                }

                $grandTotalCents = $this->toCents($quotation->grand_total);
                $totalPaidCents = $this->toCents(
                    $quotation->payments()->sum('amount')
                );
                $outstandingCents = max(
                    $grandTotalCents - $totalPaidCents,
                    0
                );
                $amountCents = $this->toCents($validated['amount']);

                if ($amountCents <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Payment amount must be at least ₹ 0.01.',
                    ]);
                }

                if ($outstandingCents <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'This quotation has no outstanding balance.',
                    ]);
                }

                if ($amountCents > $outstandingCents) {
                    throw ValidationException::withMessages([
                        'amount' => sprintf(
                            'Payment amount cannot exceed the outstanding balance of ₹ %s.',
                            number_format($outstandingCents / 100, 2),
                        ),
                    ]);
                }

                $receiptNumber = Payment::nextReceiptNumber();

                $paymentData = Arr::only($validated, [
                    'payment_method',
                    'transaction_reference',
                    'payment_date',
                    'notes',
                    'is_active',
                ]);

                $paymentData['payment_no'] = Payment::nextPaymentNumber();
                $paymentData['receipt_number'] = $receiptNumber;
                $paymentData['quotation_id'] = $quotation->getKey();
                $paymentData['customer_id'] = $quotation->customer_id;
                $paymentData['amount'] = number_format(
                    $amountCents / 100,
                    2,
                    '.',
                    '',
                );
                $paymentData['is_active'] = $paymentData['is_active'] ?? true;

                $payment = Payment::query()->create($paymentData);

                $payment->completePayment();

                $receiptGenerationStarted = true;
                $receiptPath = $this->documents->generateReceipt($payment);

                return $payment->fresh([
                    'customer',
                    'quotation',
                ]);
            });
        } catch (Throwable $exception) {
            if ($receiptGenerationStarted) {
                $this->deleteFailedReceipt(
                    $receiptPath,
                    $receiptNumber,
                );
            }

            throw $exception;
        }
    }

    /**
     * Backward-compatible entry point for already-created payments.
     */
    public static function process(Payment $payment): void
    {
        app(self::class)->completeExisting($payment);
    }

    private function completeExisting(Payment $payment): void
    {
        $receiptPath = null;
        $receiptNumber = $payment->receipt_number;
        $receiptGenerationStarted = false;

        try {
            DB::transaction(function () use (
                $payment,
                &$receiptPath,
                &$receiptGenerationStarted,
            ): void {
                $lockedPayment = Payment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->getKey());

                $lockedPayment->completePayment();

                $receiptGenerationStarted = true;
                $receiptPath = $this->documents
                    ->generateReceipt($lockedPayment);
            });
        } catch (Throwable $exception) {
            if ($receiptGenerationStarted) {
                $this->deleteFailedReceipt(
                    $receiptPath,
                    $receiptNumber,
                );
            }

            throw $exception;
        }
    }

    private function deleteFailedReceipt(
        ?string $receiptPath,
        ?string $receiptNumber,
    ): void {
        $path = $receiptPath;

        if (! $path && $receiptNumber) {
            $path = 'receipts/' . $receiptNumber . '.pdf';
        }

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
