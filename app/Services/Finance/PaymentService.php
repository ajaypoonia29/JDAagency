<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Documents\DocumentService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
                $totalPaidCents = $this->activePaymentTotalCents($quotation);
                $outstandingCents = max(
                    $grandTotalCents - $totalPaidCents,
                    0,
                );
                $amountCents = $this->toCents($validated['amount']);

                $this->assertPositiveAmount($amountCents);

                if ($outstandingCents <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'This quotation has no outstanding balance.',
                    ]);
                }

                $this->assertAmountWithinAvailableBalance(
                    $amountCents,
                    $outstandingCents,
                );

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
                $paymentData['amount'] = $this->formatCents($amountCents);
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
     * Update a payment while treating the quotation ledger as authoritative.
     *
     * Identity and receipt-security fields are intentionally not accepted.
     * The customer is always derived from the selected quotation.
     *
     * @param array<string, mixed> $data
     */
    public function update(Payment $payment, array $data): Payment
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

        $receiptSnapshot = null;
        $generatedReceiptPath = null;
        $receiptGenerationStarted = false;

        try {
            return DB::transaction(function () use (
                $payment,
                $validated,
                &$receiptSnapshot,
                &$generatedReceiptPath,
                &$receiptGenerationStarted,
            ): Payment {
                $lockedPayment = Payment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->getKey());

                $quotationIds = collect([
                    $lockedPayment->quotation_id,
                    (int) $validated['quotation_id'],
                ])
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                /** @var EloquentCollection<int, Quotation> $quotations */
                $quotations = Quotation::query()
                    ->withTrashed()
                    ->whereKey($quotationIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                /** @var Quotation|null $targetQuotation */
                $targetQuotation = $quotations->firstWhere(
                    'id',
                    (int) $validated['quotation_id'],
                );

                if (! $targetQuotation || $targetQuotation->trashed()) {
                    throw ValidationException::withMessages([
                        'quotation_id' => 'The selected quotation is unavailable.',
                    ]);
                }

                $amountCents = $this->toCents($validated['amount']);
                $this->assertPositiveAmount($amountCents);

                $availableCents = max(
                    $this->toCents($targetQuotation->grand_total)
                    - $this->activePaymentTotalCents(
                        $targetQuotation,
                        $lockedPayment->getKey(),
                    ),
                    0,
                );

                $this->assertAmountWithinAvailableBalance(
                    $amountCents,
                    $availableCents,
                );

                $paymentData = Arr::only($validated, [
                    'payment_method',
                    'transaction_reference',
                    'payment_date',
                    'notes',
                    'is_active',
                ]);

                $paymentData['quotation_id'] = $targetQuotation->getKey();
                $paymentData['customer_id'] = $targetQuotation->customer_id;
                $paymentData['amount'] = $this->formatCents($amountCents);

                $lockedPayment->fill($paymentData);

                $receiptNeedsRegeneration = $lockedPayment->receipt_generated
                    && $lockedPayment->isDirty([
                        'quotation_id',
                        'customer_id',
                        'amount',
                        'payment_method',
                        'transaction_reference',
                        'payment_date',
                        'notes',
                    ]);

                if ($receiptNeedsRegeneration) {
                    $receiptSnapshot = $this->captureReceiptSnapshot(
                        $lockedPayment,
                    );
                    $generatedReceiptPath = $this->expectedReceiptPath(
                        $lockedPayment->receipt_number,
                    );
                }

                $lockedPayment->save();

                if ($receiptNeedsRegeneration) {
                    $receiptGenerationStarted = true;
                    $generatedReceiptPath = $this->documents
                        ->generateReceipt($lockedPayment);
                }

                return $lockedPayment->fresh([
                    'customer',
                    'quotation',
                ]);
            });
        } catch (Throwable $exception) {
            if ($receiptGenerationStarted) {
                $this->restoreReceiptSnapshot(
                    $receiptSnapshot,
                    $generatedReceiptPath,
                );
            }

            throw $exception;
        }
    }

    /**
     * Soft-delete a payment and recalculate its quotation in one transaction.
     */
    public function delete(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment): bool {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            $this->lockQuotation($lockedPayment->quotation_id);

            return (bool) $lockedPayment->delete();
        });
    }

    /**
     * Restore a payment only when doing so cannot overpay the quotation.
     */
    public function restore(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment): bool {
            $lockedPayment = Payment::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            if (! $lockedPayment->trashed()) {
                return true;
            }

            $quotation = $this->lockQuotation(
                $lockedPayment->quotation_id,
            );

            if (! $quotation || $quotation->trashed()) {
                throw ValidationException::withMessages([
                    'quotation_id' => 'The payment quotation is unavailable.',
                ]);
            }

            $availableCents = max(
                $this->toCents($quotation->grand_total)
                - $this->activePaymentTotalCents($quotation),
                0,
            );

            $this->assertAmountWithinAvailableBalance(
                $this->toCents($lockedPayment->amount),
                $availableCents,
            );

            return (bool) $lockedPayment->restore();
        });
    }

    /**
     * Permanently delete a payment and remove its generated documents only
     * after the database transaction has committed successfully.
     */
    public function forceDelete(Payment $payment): bool
    {
        $documentPaths = [];

        $deleted = DB::transaction(function () use (
            $payment,
            &$documentPaths,
        ): bool {
            $lockedPayment = Payment::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($payment->getKey());

            $this->lockQuotation($lockedPayment->quotation_id);

            $documentPaths = array_values(array_filter([
                $lockedPayment->receipt_pdf,
                $lockedPayment->statement_pdf,
            ]));

            return (bool) $lockedPayment->forceDelete();
        });

        if ($deleted && $documentPaths !== []) {
            Storage::disk('public')->delete($documentPaths);
        }

        return $deleted;
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

    private function lockQuotation(int|string|null $quotationId): ?Quotation
    {
        if (! $quotationId) {
            return null;
        }

        return Quotation::query()
            ->withTrashed()
            ->whereKey($quotationId)
            ->lockForUpdate()
            ->first();
    }

    private function activePaymentTotalCents(
        Quotation $quotation,
        int|string|null $excludingPaymentId = null,
    ): int {
        $query = $quotation->payments();

        if ($excludingPaymentId) {
            $query->where('id', '!=', $excludingPaymentId);
        }

        return $this->toCents($query->sum('amount'));
    }

    private function assertPositiveAmount(int $amountCents): void
    {
        if ($amountCents <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be at least ₹ 0.01.',
            ]);
        }
    }

    private function assertAmountWithinAvailableBalance(
        int $amountCents,
        int $availableCents,
    ): void {
        if ($amountCents <= $availableCents) {
            return;
        }

        throw ValidationException::withMessages([
            'amount' => sprintf(
                'Payment amount cannot exceed the available balance of ₹ %s.',
                number_format($availableCents / 100, 2),
            ),
        ]);
    }

    /**
     * @return array{path: ?string, existed: bool, contents: ?string}
     */
    private function captureReceiptSnapshot(Payment $payment): array
    {
        $path = filled($payment->receipt_pdf)
            ? $payment->receipt_pdf
            : $this->expectedReceiptPath($payment->receipt_number);

        $existed = filled($path)
            && Storage::disk('public')->exists($path);

        return [
            'path' => $path,
            'existed' => $existed,
            'contents' => $existed
                ? Storage::disk('public')->get($path)
                : null,
        ];
    }

    /**
     * @param array{path: ?string, existed: bool, contents: ?string}|null $snapshot
     */
    private function restoreReceiptSnapshot(
        ?array $snapshot,
        ?string $generatedPath,
    ): void {
        $disk = Storage::disk('public');
        $snapshotPath = $snapshot['path'] ?? null;

        if (
            filled($generatedPath)
            && $generatedPath !== $snapshotPath
        ) {
            $disk->delete($generatedPath);
        }

        if (! filled($snapshotPath)) {
            return;
        }

        if (($snapshot['existed'] ?? false) === true) {
            $disk->put(
                $snapshotPath,
                (string) ($snapshot['contents'] ?? ''),
            );

            return;
        }

        $disk->delete($snapshotPath);
    }

    private function expectedReceiptPath(?string $receiptNumber): ?string
    {
        return filled($receiptNumber)
            ? 'receipts/' . $receiptNumber . '.pdf'
            : null;
    }

    private function deleteFailedReceipt(
        ?string $receiptPath,
        ?string $receiptNumber,
    ): void {
        $path = $receiptPath
            ?: $this->expectedReceiptPath($receiptNumber);

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function formatCents(int $amountCents): string
    {
        return number_format(
            $amountCents / 100,
            2,
            '.',
            '',
        );
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
