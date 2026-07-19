<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\CreditNote;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Refund;
use App\Services\Communication\CommunicationService;
use App\Services\Finance\CreditNoteService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\PaymentService;
use App\Services\Finance\RefundService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class SalesWorkspaceFinance extends Component
{
    #[Locked]
    public int $leadId;

    public bool $invoiceEditorOpen = false;

    public string $invoiceDate = '';

    public string $dueDate = '';

    public string $invoiceCustomerNotes = '';

    public string $invoiceInternalNotes = '';

    public bool $paymentEditorOpen = false;

    public string $paymentAmount = '';

    public string $paymentMethod = 'UPI';

    public string $paymentReference = '';

    public string $paymentDate = '';

    public string $paymentNotes = '';

    public bool $creditNoteEditorOpen = false;

    public string $creditNoteAmount = '';

    public string $creditNoteTax = '0.00';

    public string $creditNoteIssueDate = '';

    public string $creditNoteDescription = '';

    public string $creditNoteReason = '';

    public bool $refundEditorOpen = false;

    public ?int $refundPaymentId = null;

    public ?int $refundCreditNoteId = null;

    public string $refundAmount = '';

    public string $refundMethod = 'Original Method';

    public string $refundReference = '';

    public string $refundDate = '';

    public string $refundReason = '';

    public function mount(int $leadId): void
    {
        $this->leadId = $leadId;

        $lead = $this->lead();

        Gate::authorize('view', $lead);

        $this->resetInvoiceDraft();
        $this->resetPaymentDraft();
        $this->resetCreditNoteDraft();
        $this->resetRefundDraft();
    }

    #[On('sales-workspace-updated')]
    public function refreshWorkspace(): void
    {
    }

    public function render(): View
    {
        $lead = $this->lead();

        Gate::authorize('view', $lead);

        $quotation = $this->quotation($lead);
        $invoice = $quotation
            ? $this->invoice($quotation)
            : null;

        $payments = $quotation
            ? $quotation->payments()
                ->with([
                    'customer',
                    'quotation',
                    'refunds',
                ])
                ->latest('payment_date')
                ->latest('id')
                ->get()
            : collect();

        $canViewCreditNotes = Gate::allows(
            'viewAny',
            CreditNote::class,
        );

        $canViewRefunds = Gate::allows(
            'viewAny',
            Refund::class,
        );

        $creditNotes =
            $invoice && $canViewCreditNotes
                ? $invoice->creditNotes()
                    ->with([
                        'customer',
                        'refunds',
                    ])
                    ->latest('issue_date')
                    ->latest('id')
                    ->get()
                : collect();

        $refunds =
            $invoice && $canViewRefunds
                ? $invoice->refunds()
                    ->with([
                        'creditNote',
                        'customer',
                        'payment',
                    ])
                    ->latest('refund_date')
                    ->latest('id')
                    ->get()
                : collect();

        $canCreateCreditNote =
            $invoice !== null
            && $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $this->remainingCreditAmount($invoice) > 0
            && Gate::allows(
                'createCreditNote',
                $invoice,
            );

        $canProcessRefund =
            $invoice !== null
            && $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $invoice->refundableAmount() > 0
            && Gate::allows(
                'refund',
                $invoice,
            );

        $refundablePayments =
            $invoice && $canProcessRefund
                ? $this->refundablePayments($invoice)
                : [];

        $refundPaymentOptions = collect(
            $refundablePayments,
        )->mapWithKeys(
            fn (
                array $details,
                int|string $paymentId,
            ): array => [
                (int) $paymentId =>
                    $details['label'],
            ],
        )->all();

        $refundCreditNoteOptions =
            $invoice && $canProcessRefund
                ? $this->refundableCreditNotes(
                    $invoice,
                )
                : [];

        return view(
            'livewire.sales-workspace-finance',
            [
                'lead' => $lead,
                'quotation' => $quotation,
                'invoice' => $invoice,
                'payments' => $payments,
                'creditNotes' => $creditNotes,
                'refunds' => $refunds,
                'adjustments' =>
                    $this->adjustmentTimeline(
                        $creditNotes,
                        $refunds,
                    ),
                'refundPaymentOptions' =>
                    $refundPaymentOptions,
                'refundCreditNoteOptions' =>
                    $refundCreditNoteOptions,

                'canCreateInvoice' =>
                    $quotation !== null
                    && $invoice === null
                    && in_array(
                        $quotation->status,
                        [
                            'Approved',
                            'Sent',
                            'Accepted',
                            'Completed',
                        ],
                        true,
                    )
                    && Gate::allows(
                        'create',
                        Invoice::class,
                    ),

                'canEditInvoice' =>
                    $invoice !== null
                    && $invoice->status === 'Draft'
                    && Gate::allows(
                        'update',
                        $invoice,
                    ),

                'canIssueInvoice' =>
                    $invoice !== null
                    && $invoice->status === 'Draft'
                    && Gate::allows(
                        'issue',
                        $invoice,
                    ),

                'canSendInvoice' =>
                    $invoice !== null
                    && $invoice->issued_at !== null
                    && $invoice->status !== 'Void'
                    && filled($invoice->invoice_pdf)
                    && Gate::allows(
                        'send',
                        $invoice,
                    ),

                'canDownloadInvoice' =>
                    $invoice !== null
                    && filled($invoice->invoice_pdf)
                    && Gate::allows(
                        'download',
                        $invoice,
                    ),

                'canRecordPayment' =>
                    $quotation !== null
                    && $invoice !== null
                    && $invoice->issued_at !== null
                    && $invoice->status !== 'Void'
                    && (float) $invoice->balance_due > 0
                    && Gate::allows(
                        'create',
                        Payment::class,
                    ),

                'canCreateCreditNote' =>
                    $canCreateCreditNote,

                'canProcessRefund' =>
                    $canProcessRefund
                    && $refundPaymentOptions !== [],

                'canViewAdjustments' =>
                    $canViewCreditNotes
                    || $canViewRefunds,
            ],
        );
    }

    public function openInvoiceEditor(): void
    {
        $lead = $this->lead();

        Gate::authorize('view', $lead);

        $quotation = $this->quotationOrFail($lead);
        $invoice = $this->invoice($quotation);

        if ($invoice) {
            Gate::authorize(
                'update',
                $invoice,
            );

            abort_unless(
                $invoice->status === 'Draft',
                422,
            );

            $this->invoiceDate =
                $invoice->invoice_date?->format('Y-m-d')
                ?? now()->format('Y-m-d');

            $this->dueDate =
                $invoice->due_date?->format('Y-m-d')
                ?? now()->addDays(7)->format('Y-m-d');

            $this->invoiceCustomerNotes = (string) (
                $invoice->customer_notes
                ?? ''
            );

            $this->invoiceInternalNotes = (string) (
                $invoice->internal_notes
                ?? ''
            );
        } else {
            Gate::authorize(
                'create',
                Invoice::class,
            );

            abort_unless(
                in_array(
                    $quotation->status,
                    [
                        'Approved',
                        'Sent',
                        'Accepted',
                        'Completed',
                    ],
                    true,
                ),
                422,
            );

            $this->resetInvoiceDraft();

            $this->invoiceCustomerNotes = (string) (
                $quotation->customer_notes
                ?? ''
            );
        }

        $this->invoiceEditorOpen = true;
        $this->paymentEditorOpen = false;
        $this->creditNoteEditorOpen = false;
        $this->refundEditorOpen = false;

        $this->resetValidation();
    }

    public function closeInvoiceEditor(): void
    {
        $this->invoiceEditorOpen = false;

        $this->resetValidation();
    }

    public function saveInvoice(
        InvoiceService $invoices,
    ): void {
        $lead = $this->lead();

        Gate::authorize('view', $lead);

        $quotation = $this->quotationOrFail($lead);
        $invoice = $this->invoice($quotation);

        $validated = $this->validate([
            'invoiceDate' => [
                'required',
                'date',
            ],
            'dueDate' => [
                'required',
                'date',
                'after_or_equal:invoiceDate',
            ],
            'invoiceCustomerNotes' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'invoiceInternalNotes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $payload = [
            'invoice_date' =>
                $validated['invoiceDate'],

            'due_date' =>
                $validated['dueDate'],

            'customer_notes' =>
                $validated['invoiceCustomerNotes']
                ?: null,

            'internal_notes' =>
                $validated['invoiceInternalNotes']
                ?: null,

            'is_active' => true,
        ];

        if ($invoice) {
            Gate::authorize(
                'update',
                $invoice,
            );

            $invoice = $invoices->update(
                $invoice,
                $payload,
            );

            $message = sprintf(
                'Invoice %s updated.',
                $invoice->invoice_no,
            );
        } else {
            Gate::authorize(
                'create',
                Invoice::class,
            );

            $invoice = $invoices->createFromQuotation(
                $quotation,
                $payload,
            );

            $message = sprintf(
                'Invoice %s created as draft.',
                $invoice->invoice_no,
            );
        }

        $this->invoiceEditorOpen = false;

        $this->dispatch(
            'sales-workspace-updated',
        );

        $this->notifySuccess($message);
    }

    public function issueInvoice(
        InvoiceService $invoices,
    ): void {
        $invoice = $this->invoiceOrFail();

        Gate::authorize(
            'issue',
            $invoice,
        );

        $issued = $invoices->issue($invoice);

        $this->dispatch(
            'sales-workspace-updated',
        );

        $this->notifySuccess(
            sprintf(
                'Invoice %s issued successfully.',
                $issued->invoice_no,
            ),
        );
    }

    public function sendInvoice(): void
    {
        $invoice = $this->invoiceOrFail();

        Gate::authorize(
            'send',
            $invoice,
        );

        $sent =
            CommunicationService::sendInvoiceViaEmail(
                $invoice->refresh(),
            );

        $invoice->refresh();

        $notification = Notification::make()
            ->title(
                $sent
                    ? 'Invoice emailed successfully.'
                    : 'Unable to send invoice email.',
            )
            ->body(
                $sent
                    ? 'Sent to: '
                        . $invoice->last_sent_to
                    : (
                        $invoice->last_delivery_error
                        ?: 'Check the application log.'
                    ),
            );

        if ($sent) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->send();

        $this->dispatch(
            'sales-workspace-updated',
        );
    }

    public function openPaymentEditor(): void
    {
        $invoice = $this->invoiceOrFail();

        Gate::authorize(
            'create',
            Payment::class,
        );

        abort_unless(
            $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && (float) $invoice->balance_due > 0,
            422,
        );

        $this->resetPaymentDraft();

        $this->paymentAmount = number_format(
            (float) $invoice->balance_due,
            2,
            '.',
            '',
        );

        $this->paymentEditorOpen = true;
        $this->invoiceEditorOpen = false;
        $this->creditNoteEditorOpen = false;
        $this->refundEditorOpen = false;

        $this->resetValidation();
    }

    public function closePaymentEditor(): void
    {
        $this->paymentEditorOpen = false;

        $this->resetValidation();
    }

    public function recordPayment(
        PaymentService $payments,
    ): void {
        $lead = $this->lead();

        Gate::authorize('view', $lead);

        $quotation =
            $this->quotationOrFail($lead);

        $invoice =
            $this->invoiceOrFail($quotation);

        Gate::authorize(
            'create',
            Payment::class,
        );

        abort_unless(
            $invoice->issued_at !== null
            && $invoice->status !== 'Void',
            422,
        );

        $validated = $this->validate([
            'paymentAmount' => [
                'required',
                'numeric',
                'gt:0',
                'lte:' . number_format(
                    (float) $invoice->balance_due,
                    2,
                    '.',
                    '',
                ),
            ],

            'paymentMethod' => [
                'required',
                Rule::in([
                    'Cash',
                    'UPI',
                    'Bank Transfer',
                    'Cheque',
                    'Credit Card',
                    'Debit Card',
                ]),
            ],

            'paymentReference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'paymentDate' => [
                'required',
                'date',
            ],

            'paymentNotes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $payment = $payments->create([
            'quotation_id' =>
                $quotation->getKey(),

            'amount' =>
                $validated['paymentAmount'],

            'payment_method' =>
                $validated['paymentMethod'],

            'transaction_reference' =>
                $validated['paymentReference']
                ?: null,

            'payment_date' =>
                $validated['paymentDate'],

            'notes' =>
                $validated['paymentNotes']
                ?: null,

            'is_active' => true,
        ]);

        $this->paymentEditorOpen = false;

        $this->dispatch(
            'sales-workspace-updated',
        );

        $this->notifySuccess(
            sprintf(
                'Payment %s recorded and allocated.',
                $payment->payment_no,
            ),
        );
    }

    public function openCreditNoteEditor(): void
    {
        $invoice = $this->invoiceOrFail();

        Gate::authorize(
            'createCreditNote',
            $invoice,
        );

        abort_unless(
            $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $this->remainingCreditAmount(
                $invoice,
            ) > 0,
            422,
        );

        $this->resetCreditNoteDraft($invoice);

        $this->creditNoteEditorOpen = true;
        $this->invoiceEditorOpen = false;
        $this->paymentEditorOpen = false;
        $this->refundEditorOpen = false;

        $this->resetValidation();
    }

    public function closeCreditNoteEditor(): void
    {
        $this->creditNoteEditorOpen = false;

        $this->resetValidation();
    }

    public function issueCreditNote(
        CreditNoteService $creditNotes,
    ): void {
        $invoice = $this->invoiceOrFail();

        Gate::authorize(
            'createCreditNote',
            $invoice,
        );

        $remainingCredit =
            $this->remainingCreditAmount($invoice);

        abort_unless(
            $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $remainingCredit > 0,
            422,
        );

        $validated = $this->validate([
            'creditNoteAmount' => [
                'required',
                'numeric',
                'gt:0',
                'lte:' . number_format(
                    $remainingCredit,
                    2,
                    '.',
                    '',
                ),
            ],

            'creditNoteTax' => [
                'required',
                'numeric',
                'gte:0',
                'lte:creditNoteAmount',
            ],

            'creditNoteIssueDate' => [
                'required',
                'date',
            ],

            'creditNoteDescription' => [
                'nullable',
                'string',
                'max:255',
            ],

            'creditNoteReason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $creditNote =
            $creditNotes->createAndIssue(
                $invoice,
                [
                    'amount' =>
                        $validated[
                            'creditNoteAmount'
                        ],

                    'tax' =>
                        $validated[
                            'creditNoteTax'
                        ],

                    'issue_date' =>
                        $validated[
                            'creditNoteIssueDate'
                        ],

                    'description' =>
                        $validated[
                            'creditNoteDescription'
                        ] ?: null,

                    'reason' =>
                        $validated[
                            'creditNoteReason'
                        ],
                ],
            );

        $this->creditNoteEditorOpen = false;
        $this->resetCreditNoteDraft();

        $this->dispatch(
            'sales-workspace-updated',
        );

        $this->notifySuccess(
            sprintf(
                'Credit note %s issued.',
                $creditNote->credit_note_no,
            ),
        );
    }

    public function openRefundEditor(): void
    {
        $invoice = $this->invoiceOrFail();

        Gate::authorize(
            'refund',
            $invoice,
        );

        $refundablePayments =
            $this->refundablePayments($invoice);

        abort_unless(
            $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $invoice->refundableAmount() > 0
            && $refundablePayments !== [],
            422,
        );

        $this->resetRefundDraft(
            $invoice,
            $refundablePayments,
        );

        $this->refundEditorOpen = true;
        $this->invoiceEditorOpen = false;
        $this->paymentEditorOpen = false;
        $this->creditNoteEditorOpen = false;

        $this->resetValidation();
    }

    public function closeRefundEditor(): void
    {
        $this->refundEditorOpen = false;

        $this->resetValidation();
    }

    public function processRefund(
        RefundService $refunds,
    ): void {
        $invoice = $this->invoiceOrFail();

        Gate::authorize(
            'refund',
            $invoice,
        );

        abort_unless(
            $invoice->issued_at !== null
            && $invoice->status !== 'Void'
            && $invoice->refundableAmount() > 0,
            422,
        );

        $validated = $this->validate([
            'refundPaymentId' => [
                'required',
                'integer',
            ],

            'refundCreditNoteId' => [
                'nullable',
                'integer',
            ],

            'refundAmount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'refundMethod' => [
                'required',
                Rule::in([
                    'Original Method',
                    'Cash',
                    'UPI',
                    'Bank Transfer',
                    'Cheque',
                    'Credit Card',
                    'Debit Card',
                ]),
            ],

            'refundReference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'refundDate' => [
                'required',
                'date',
            ],

            'refundReason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $refund = $refunds->process(
            $invoice,
            [
                'payment_id' =>
                    (int) $validated[
                        'refundPaymentId'
                    ],

                'credit_note_id' =>
                    filled(
                        $validated[
                            'refundCreditNoteId'
                        ] ?? null,
                    )
                        ? (int) $validated[
                            'refundCreditNoteId'
                        ]
                        : null,

                'amount' =>
                    $validated['refundAmount'],

                'refund_method' =>
                    $validated['refundMethod'],

                'transaction_reference' =>
                    $validated['refundReference']
                    ?: null,

                'refund_date' =>
                    $validated['refundDate'],

                'reason' =>
                    $validated['refundReason'],
            ],
        );

        $this->refundEditorOpen = false;
        $this->resetRefundDraft();

        $this->dispatch(
            'sales-workspace-updated',
        );

        $this->notifySuccess(
            sprintf(
                'Refund %s processed.',
                $refund->refund_no,
            ),
        );
    }

    private function lead(): Lead
    {
        $lead = $this->scopedLeadQuery()
            ->with([
                'convertedCustomer',
                'quotations.invoice',
                'quotations.payments',
            ])
            ->findOrFail($this->leadId);

        Gate::authorize(
            'view',
            $lead,
        );

        return $lead;
    }

    private function quotation(
        ?Lead $lead = null,
    ): ?Quotation {
        $lead ??= $this->lead();

        return $lead->quotations()
            ->with([
                'customer',
                'items.service',
                'invoice',
                'payments',
            ])
            ->latest('id')
            ->first();
    }

    private function quotationOrFail(
        ?Lead $lead = null,
    ): Quotation {
        $quotation = $this->quotation($lead);

        abort_unless(
            $quotation instanceof Quotation,
            404,
        );

        Gate::authorize(
            'view',
            $quotation,
        );

        return $quotation;
    }

    private function invoice(
        Quotation $quotation,
    ): ?Invoice {
        return $quotation->invoice()
            ->with([
                'customer',
                'lead',
                'quotation',
                'items',
                'allocations.payment',
            ])
            ->first();
    }

    private function invoiceOrFail(
        ?Quotation $quotation = null,
    ): Invoice {
        $quotation ??=
            $this->quotationOrFail();

        $invoice =
            $this->invoice($quotation);

        abort_unless(
            $invoice instanceof Invoice,
            404,
        );

        Gate::authorize(
            'view',
            $invoice,
        );

        return $invoice;
    }

    private function scopedLeadQuery(): Builder
    {
        $query = Lead::query();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (
            method_exists(
                $user,
                'hasAnyRole',
            )
            && $user->hasAnyRole([
                'Admin',
                'Developer',
                'Sales Manager',
                'Manager',
                'General Manager',
                'Director',
                'Chief Executive Officer',
            ])
        ) {
            return $query;
        }

        $employeeId = Employee::query()
            ->where(
                'user_id',
                $user->getKey(),
            )
            ->value('id');

        if (! $employeeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'assigned_employee_id',
            $employeeId,
        );
    }

    private function remainingCreditAmount(
        Invoice $invoice,
    ): float {
        $issuedTotal = round(
            (float) $invoice->creditNotes()
                ->where('status', 'Issued')
                ->sum('grand_total'),
            2,
        );

        return round(
            max(
                (float) $invoice->grand_total
                - $issuedTotal,
                0,
            ),
            2,
        );
    }

    /**
     * @return array<int, array{
     *     label: string,
     *     available: float
     * }>
     */
    private function refundablePayments(
        Invoice $invoice,
    ): array {
        return $invoice->allocations()
            ->with('payment')
            ->whereHas('payment')
            ->get()
            ->mapWithKeys(function (
                mixed $allocation,
            ) use ($invoice): array {
                $payment = $allocation->payment;

                if (! $payment instanceof Payment) {
                    return [];
                }

                $refunded = round(
                    (float) $payment->refunds()
                        ->where(
                            'invoice_id',
                            $invoice->getKey(),
                        )
                        ->where(
                            'status',
                            'Processed',
                        )
                        ->sum('amount'),
                    2,
                );

                $available = round(
                    max(
                        (float) $allocation->amount
                        - $refunded,
                        0,
                    ),
                    2,
                );

                if ($available <= 0) {
                    return [];
                }

                return [
                    (int) $payment->getKey() => [
                        'label' => sprintf(
                            '%s · %s · ₹%s available',
                            $payment->payment_no,
                            $payment->payment_method,
                            number_format(
                                $available,
                                2,
                            ),
                        ),
                        'available' => $available,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function refundableCreditNotes(
        Invoice $invoice,
    ): array {
        return $invoice->creditNotes()
            ->with('refunds')
            ->where('status', 'Issued')
            ->orderBy('credit_note_no')
            ->get()
            ->mapWithKeys(function (
                CreditNote $creditNote,
            ): array {
                $refunded = round(
                    (float) $creditNote->refunds()
                        ->where(
                            'status',
                            'Processed',
                        )
                        ->sum('amount'),
                    2,
                );

                $available = round(
                    max(
                        (float) $creditNote->grand_total
                        - $refunded,
                        0,
                    ),
                    2,
                );

                if ($available <= 0) {
                    return [];
                }

                return [
                    (int) $creditNote->getKey() =>
                        sprintf(
                            '%s · ₹%s available',
                            $creditNote->credit_note_no,
                            number_format(
                                $available,
                                2,
                            ),
                        ),
                ];
            })
            ->all();
    }

    /**
     * @param Collection<int, CreditNote> $creditNotes
     * @param Collection<int, Refund> $refunds
     * @return Collection<int, array{
     *     kind: string,
     *     occurredAt: mixed,
     *     record: CreditNote|Refund
     * }>
     */
    private function adjustmentTimeline(
        Collection $creditNotes,
        Collection $refunds,
    ): Collection {
        return $creditNotes
            ->map(
                fn (
                    CreditNote $creditNote,
                ): array => [
                    'kind' => 'credit-note',
                    'occurredAt' =>
                        $creditNote->issued_at
                        ?? $creditNote->issue_date
                        ?? $creditNote->created_at,
                    'record' => $creditNote,
                ],
            )
            ->concat(
                $refunds->map(
                    fn (
                        Refund $refund,
                    ): array => [
                        'kind' => 'refund',
                        'occurredAt' =>
                            $refund->processed_at
                            ?? $refund->refund_date
                            ?? $refund->created_at,
                        'record' => $refund,
                    ],
                ),
            )
            ->sortByDesc(
                fn (array $adjustment): int =>
                    $adjustment['occurredAt']
                        ?->getTimestamp()
                    ?? 0,
            )
            ->values();
    }

    private function resetInvoiceDraft(): void
    {
        $this->invoiceDate =
            now()->format('Y-m-d');

        $this->dueDate =
            now()
                ->addDays(7)
                ->format('Y-m-d');

        $this->invoiceCustomerNotes = '';
        $this->invoiceInternalNotes = '';
    }

    private function resetPaymentDraft(): void
    {
        $this->paymentAmount = '';
        $this->paymentMethod = 'UPI';
        $this->paymentReference = '';

        $this->paymentDate =
            now()->format('Y-m-d');

        $this->paymentNotes = '';
    }

    private function resetCreditNoteDraft(
        ?Invoice $invoice = null,
    ): void {
        $this->creditNoteAmount =
            $invoice
                ? number_format(
                    $this->remainingCreditAmount(
                        $invoice,
                    ),
                    2,
                    '.',
                    '',
                )
                : '';

        $this->creditNoteTax = '0.00';

        $this->creditNoteIssueDate =
            now()->format('Y-m-d');

        $this->creditNoteDescription = '';
        $this->creditNoteReason = '';
    }

    /**
     * @param array<int, array{
     *     label: string,
     *     available: float
     * }> $refundablePayments
     */
    private function resetRefundDraft(
        ?Invoice $invoice = null,
        array $refundablePayments = [],
    ): void {
        $firstPaymentId =
            array_key_first($refundablePayments);

        $this->refundPaymentId =
            $firstPaymentId !== null
                ? (int) $firstPaymentId
                : null;

        $this->refundCreditNoteId = null;

        $available =
            $firstPaymentId !== null
                ? (float) $refundablePayments[
                    $firstPaymentId
                ]['available']
                : 0;

        $this->refundAmount =
            $invoice && $available > 0
                ? number_format(
                    min(
                        $available,
                        $invoice->refundableAmount(),
                    ),
                    2,
                    '.',
                    '',
                )
                : '';

        $this->refundMethod =
            'Original Method';

        $this->refundReference = '';

        $this->refundDate =
            now()->format('Y-m-d');

        $this->refundReason = '';
    }

    private function notifySuccess(
        string $message,
    ): void {
        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }
}