<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\Communication\CommunicationService;
use App\Services\Finance\InvoiceService;
use App\Services\Finance\PaymentService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
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

    public function mount(int $leadId): void
    {
        $this->leadId = $leadId;

        $lead = $this->lead();

        Gate::authorize('view', $lead);

        $this->resetInvoiceDraft();
        $this->resetPaymentDraft();
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
                ])
                ->latest('payment_date')
                ->latest('id')
                ->get()
            : collect();

        return view(
            'livewire.sales-workspace-finance',
            [
                'lead' => $lead,
                'quotation' => $quotation,
                'invoice' => $invoice,
                'payments' => $payments,

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

    private function notifySuccess(
        string $message,
    ): void {
        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }
}