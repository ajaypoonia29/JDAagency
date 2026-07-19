<div id="asw-finance-workspace" class="asw-card asw-section aswf">
    <style>
        .aswf {
            scroll-margin-top: 1rem;
        }

        .aswf-head,
        .aswf-actions,
        .aswf-summary-row,
        .aswf-payment-head,
        .aswf-payment-actions,
        .aswf-adjustment-head,
        .aswf-adjustment-actions {
            align-items: center;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .aswf-actions,
        .aswf-payment-actions,
        .aswf-adjustment-actions {
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .aswf-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 1rem;
        }

        .aswf-grid--four {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .aswf-panel {
            background: var(--asw-soft);
            border: 1px solid var(--asw-border);
            border-radius: 0.85rem;
            padding: 1rem;
        }

        .aswf-panel__label {
            color: var(--asw-muted);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .aswf-panel__value {
            color: var(--asw-text);
            font-size: 1rem;
            font-weight: 750;
            margin-top: 0.35rem;
            overflow-wrap: anywhere;
        }

        .aswf-panel__value--money {
            font-size: 1.15rem;
        }

        .aswf-summary {
            background: var(--asw-soft);
            border-radius: 0.85rem;
            display: grid;
            gap: 0.65rem;
            margin-top: 1rem;
            padding: 1rem;
        }

        .aswf-summary-row {
            color: var(--asw-muted);
            font-size: 0.85rem;
        }

        .aswf-summary-row strong {
            color: var(--asw-text);
            text-align: right;
        }

        .aswf-summary-row.is-total {
            border-top: 1px solid var(--asw-border);
            color: var(--asw-text);
            font-size: 1rem;
            font-weight: 800;
            padding-top: 0.7rem;
        }

        .aswf-notice {
            border: 1px solid var(--asw-border);
            border-radius: 0.8rem;
            font-size: 0.84rem;
            line-height: 1.55;
            margin-top: 1rem;
            padding: 0.85rem 1rem;
        }

        .aswf-notice--warning {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #92400e;
        }

        .aswf-notice--success {
            background: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }

        .aswf-notice--danger {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        .dark .aswf-notice--warning {
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.4);
            color: #fcd34d;
        }

        .dark .aswf-notice--success {
            background: rgba(34, 197, 94, 0.12);
            border-color: rgba(34, 197, 94, 0.4);
            color: #86efac;
        }

        .dark .aswf-notice--danger {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .aswf-form {
            border-top: 1px solid var(--asw-border);
            margin-top: 1rem;
            padding-top: 1rem;
            scroll-margin-top: 5rem;
        }

        .aswf-form-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .aswf-field {
            display: block;
        }

        .aswf-field--full {
            grid-column: 1 / -1;
        }

        .aswf-field label {
            color: var(--asw-muted);
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .aswf-control {
            background: var(--asw-surface);
            border: 1px solid var(--asw-border);
            border-radius: 0.65rem;
            color: var(--asw-text);
            font: inherit;
            font-size: 0.84rem;
            min-height: 2.5rem;
            outline: none;
            padding: 0.58rem 0.7rem;
            width: 100%;
        }

        .aswf-control:focus {
            border-color: var(--asw-primary);
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.14);
        }

        textarea.aswf-control {
            min-height: 5rem;
            resize: vertical;
        }

        .aswf-error {
            color: #dc2626;
            display: block;
            font-size: 0.72rem;
            margin-top: 0.3rem;
        }

        .aswf-form-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        .aswf-payments {
            border-top: 1px solid var(--asw-border);
            display: grid;
            gap: 0;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .aswf-payment {
            border-bottom: 1px solid var(--asw-border);
            padding: 0.9rem 0;
        }

        .aswf-payment:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .aswf-payment__number {
            color: var(--asw-text);
            font-weight: 750;
        }

        .aswf-payment__meta {
            color: var(--asw-muted);
            font-size: 0.78rem;
            line-height: 1.5;
            margin-top: 0.25rem;
        }

        .aswf-payment__amount {
            color: var(--asw-text);
            font-size: 1rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .aswf-adjustments {
            border-top: 1px solid var(--asw-border);
            display: grid;
            gap: 0;
            margin-top: 1rem;
            padding-top: 1rem;
        }

        .aswf-adjustment {
            border-bottom: 1px solid var(--asw-border);
            padding: 0.9rem 0;
        }

        .aswf-adjustment:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .aswf-adjustment__title {
            align-items: center;
            color: var(--asw-text);
            display: flex;
            flex-wrap: wrap;
            font-weight: 750;
            gap: 0.5rem;
        }

        .aswf-adjustment__meta {
            color: var(--asw-muted);
            font-size: 0.78rem;
            line-height: 1.55;
            margin-top: 0.3rem;
        }

        .aswf-adjustment__amount {
            color: var(--asw-text);
            font-size: 1rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .aswf-badge {
            background: var(--asw-soft);
            border: 1px solid var(--asw-border);
            border-radius: 999px;
            color: var(--asw-muted);
            display: inline-flex;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            padding: 0.2rem 0.5rem;
            text-transform: uppercase;
        }

        .aswf-badge--credit {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #92400e;
        }

        .aswf-badge--refund {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        .dark .aswf-badge--credit {
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.4);
            color: #fcd34d;
        }

        .dark .aswf-badge--refund {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .aswf-document-link {
            color: var(--asw-primary);
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
        }

        .aswf-document-link:hover {
            text-decoration: underline;
        }

        .aswf-empty {
            color: var(--asw-muted);
            font-size: 0.86rem;
            line-height: 1.6;
            margin-top: 1rem;
        }

        @media (max-width: 1100px) {
            .aswf-grid--four {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .aswf-head,
            .aswf-payment-head,
            .aswf-adjustment-head {
                align-items: stretch;
                flex-direction: column;
            }

            .aswf-actions,
            .aswf-payment-actions,
            .aswf-adjustment-actions,
            .aswf-form-actions {
                justify-content: flex-start;
            }

            .aswf-grid,
            .aswf-grid--four,
            .aswf-form-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .aswf-field--full {
                grid-column: auto;
            }

            .aswf-summary-row {
                align-items: flex-start;
            }
        }
    </style>

    <div class="aswf-head">
        <div>
            <h2 class="asw-section__title">
                Invoice and collections
            </h2>

            <div class="asw-muted">
                Create and issue the invoice, collect payments,
                process authorized credits and refunds, and access
                finance documents without leaving the sales journey.
            </div>
        </div>

        <div class="aswf-actions">
            @if ($canCreateInvoice)
                <x-filament::button wire:click="openInvoiceEditor">
                    Create Invoice
                </x-filament::button>
            @endif

            @if ($canEditInvoice)
                <x-filament::button
                    color="gray"
                    outlined
                    wire:click="openInvoiceEditor"
                >
                    Edit Draft
                </x-filament::button>
            @endif

            @if ($canIssueInvoice)
                <x-filament::button
                    color="success"
                    wire:click="issueInvoice"
                    wire:confirm="Issue this invoice? Its commercial snapshot will become final."
                    wire:loading.attr="disabled"
                >
                    {{ (float) $invoice->total_paid > 0
                        ? 'Issue and Reconcile'
                        : 'Issue Invoice' }}
                </x-filament::button>
            @endif

            @if ($canSendInvoice)
                <x-filament::button
                    color="info"
                    wire:click="sendInvoice"
                    wire:confirm="Email this invoice to the customer's primary email address?"
                    wire:loading.attr="disabled"
                >
                    {{ (int) $invoice->email_send_count > 0
                        ? 'Resend Invoice'
                        : 'Email Invoice' }}
                </x-filament::button>
            @endif

            @if ($canDownloadInvoice)
                <x-filament::button
                    tag="a"
                    color="gray"
                    outlined
                    :href="route(
                        'finance.invoices.download',
                        $invoice
                    )"
                    target="_blank"
                >
                    Download Invoice
                </x-filament::button>
            @endif

            @if ($canRecordPayment)
                <x-filament::button
                    color="warning"
                    wire:click="openPaymentEditor"
                >
                    Record Payment
                </x-filament::button>
            @endif

            @if ($canCreateCreditNote)
                <x-filament::button
                    color="warning"
                    outlined
                    wire:click="openCreditNoteEditor"
                >
                    Issue Credit Note
                </x-filament::button>
            @endif

            @if ($canProcessRefund)
                <x-filament::button
                    color="danger"
                    outlined
                    wire:click="openRefundEditor"
                >
                    Process Refund
                </x-filament::button>
            @endif
        </div>
    </div>

    @if (! $quotation)
        <div class="aswf-empty">
            Create and approve a quotation before starting
            the invoice and payment lifecycle.
        </div>
    @elseif (
        ! $invoice
        && ! in_array(
            $quotation->status,
            ['Approved', 'Sent', 'Accepted', 'Completed'],
            true
        )
    )
        <div class="aswf-notice aswf-notice--warning">
            Quotation
            <strong>{{ $quotation->quotation_code }}</strong>
            is currently
            <strong>{{ $quotation->status }}</strong>.
            Approve it before creating an invoice.
        </div>
    @elseif ($quotation && ! $invoice && ! $canCreateInvoice)
        <div class="aswf-empty">
            No invoice exists for this quotation. Your current
            role does not have permission to create one.
        </div>
    @endif

    @if ($quotation)
        <div class="aswf-grid aswf-grid--four">
            <div class="aswf-panel">
                <div class="aswf-panel__label">Quotation</div>

                <div class="aswf-panel__value">
                    {{ $quotation->quotation_code }}
                </div>
            </div>

            <div class="aswf-panel">
                <div class="aswf-panel__label">
                    Quotation status
                </div>

                <div class="aswf-panel__value">
                    {{ $quotation->status }}
                </div>
            </div>

            <div class="aswf-panel">
                <div class="aswf-panel__label">
                    Quotation value
                </div>

                <div class="aswf-panel__value aswf-panel__value--money">
                    ₹{{ number_format(
                        (float) $quotation->grand_total,
                        2
                    ) }}
                </div>
            </div>

            <div class="aswf-panel">
                <div class="aswf-panel__label">
                    Quotation balance
                </div>

                <div class="aswf-panel__value aswf-panel__value--money">
                    ₹{{ number_format(
                        (float) $quotation->balance_due,
                        2
                    ) }}
                </div>
            </div>
        </div>
    @endif

    @if ($invoice)
        <div class="aswf-summary">
            <div class="aswf-summary-row">
                <span>Invoice</span>
                <strong>{{ $invoice->invoice_no }}</strong>
            </div>

            <div class="aswf-summary-row">
                <span>Status</span>
                <strong>{{ $invoice->status }}</strong>
            </div>

            <div class="aswf-summary-row">
                <span>Invoice date</span>
                <strong>
                    {{ $invoice->invoice_date?->format('d M Y')
                        ?: 'Not set' }}
                </strong>
            </div>

            <div class="aswf-summary-row">
                <span>Due date</span>
                <strong>
                    {{ $invoice->due_date?->format('d M Y')
                        ?: 'Not set' }}
                </strong>
            </div>

            <div class="aswf-summary-row">
                <span>Original invoice value</span>
                <strong>
                    ₹{{ number_format(
                        (float) $invoice->grand_total,
                        2
                    ) }}
                </strong>
            </div>

            <div class="aswf-summary-row">
                <span>Credit notes</span>
                <strong>
                    ₹{{ number_format(
                        (float) $invoice->credited_total,
                        2
                    ) }}
                </strong>
            </div>

            <div class="aswf-summary-row">
                <span>Net invoice value</span>
                <strong>
                    ₹{{ number_format(
                        (float) $invoice->net_total,
                        2
                    ) }}
                </strong>
            </div>

            <div class="aswf-summary-row">
                <span>Refunded</span>
                <strong>
                    ₹{{ number_format(
                        (float) $invoice->refunded_total,
                        2
                    ) }}
                </strong>
            </div>

            <div class="aswf-summary-row">
                <span>Net paid</span>
                <strong>
                    ₹{{ number_format(
                        (float) $invoice->total_paid,
                        2
                    ) }}
                </strong>
            </div>

            <div class="aswf-summary-row is-total">
                <span>Balance due</span>
                <span>
                    ₹{{ number_format(
                        (float) $invoice->balance_due,
                        2
                    ) }}
                </span>
            </div>
        </div>

        @if (
            $invoice->status === 'Draft'
            && (float) $invoice->total_paid > 0
        )
            <div class="aswf-notice aswf-notice--warning">
                This draft invoice already has
                <strong>
                    ₹{{ number_format(
                        (float) $invoice->total_paid,
                        2
                    ) }}
                </strong>
                in allocated payments.

                Use
                <strong>Issue and Reconcile</strong>
                to explicitly issue the invoice and let the
                authoritative ledger calculate its final status.
            </div>
        @elseif (
            $invoice->issued_at !== null
            && (float) $invoice->balance_due <= 0
        )
            <div class="aswf-notice aswf-notice--success">
                This invoice is fully settled. No additional
                payment can be recorded. Authorized finance users
                may still issue a credit note or process a refund.
            </div>
        @elseif ($invoice->status === 'Void')
            <div class="aswf-notice aswf-notice--danger">
                This invoice is void and cannot receive payments.
            </div>
        @endif

        @if ($invoice->issued_at !== null)
            <div class="aswf-grid">
                <div class="aswf-panel">
                    <div class="aswf-panel__label">
                        Delivery status
                    </div>

                    <div class="aswf-panel__value">
                        {{ $invoice->email_sent
                            ? 'Sent'
                            : 'Not sent' }}
                    </div>

                    <div class="asw-muted" style="margin-top: 0.35rem;">
                        {{ $invoice->last_sent_to
                            ?: 'No recipient recorded' }}
                    </div>
                </div>

                <div class="aswf-panel">
                    <div class="aswf-panel__label">
                        Email attempts
                    </div>

                    <div class="aswf-panel__value">
                        {{ (int) $invoice->email_send_count }}
                    </div>

                    <div class="asw-muted" style="margin-top: 0.35rem;">
                        {{ $invoice->email_sent_at
                            ? $invoice->email_sent_at->format(
                                'd M Y H:i'
                            )
                            : 'Never sent' }}
                    </div>
                </div>
            </div>

            @if ($invoice->last_delivery_error)
                <div class="aswf-notice aswf-notice--danger">
                    {{ $invoice->last_delivery_error }}
                </div>
            @endif
        @endif
    @endif

    @if ($invoiceEditorOpen)
        <form
            wire:submit="saveInvoice"
            class="aswf-form"
        >
            <div class="aswf-form-grid">
                <div class="aswf-field">
                    <label for="aswf-invoice-date">
                        Invoice date
                    </label>

                    <input
                        id="aswf-invoice-date"
                        type="date"
                        class="aswf-control"
                        wire:model="invoiceDate"
                    >

                    @error('invoiceDate')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-due-date">
                        Due date
                    </label>

                    <input
                        id="aswf-due-date"
                        type="date"
                        class="aswf-control"
                        wire:model="dueDate"
                    >

                    @error('dueDate')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field aswf-field--full">
                    <label for="aswf-customer-notes">
                        Customer notes
                    </label>

                    <textarea
                        id="aswf-customer-notes"
                        class="aswf-control"
                        wire:model="invoiceCustomerNotes"
                    ></textarea>

                    @error('invoiceCustomerNotes')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field aswf-field--full">
                    <label for="aswf-internal-notes">
                        Internal notes
                    </label>

                    <textarea
                        id="aswf-internal-notes"
                        class="aswf-control"
                        wire:model="invoiceInternalNotes"
                    ></textarea>

                    @error('invoiceInternalNotes')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <div class="aswf-form-actions">
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    wire:click="closeInvoiceEditor"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="saveInvoice"
                >
                    {{ $invoice
                        ? 'Save Invoice Draft'
                        : 'Create Invoice Draft' }}
                </x-filament::button>
            </div>
        </form>
    @endif

    @if ($paymentEditorOpen)
        <form
            wire:submit="recordPayment"
            class="aswf-form"
        >
            <div class="aswf-form-grid">
                <div class="aswf-field">
                    <label for="aswf-payment-amount">
                        Payment amount
                    </label>

                    <input
                        id="aswf-payment-amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="aswf-control"
                        wire:model="paymentAmount"
                    >

                    @error('paymentAmount')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-payment-method">
                        Payment method
                    </label>

                    <select
                        id="aswf-payment-method"
                        class="aswf-control"
                        wire:model="paymentMethod"
                    >
                        @foreach ([
                            'Cash',
                            'UPI',
                            'Bank Transfer',
                            'Cheque',
                            'Credit Card',
                            'Debit Card',
                        ] as $method)
                            <option value="{{ $method }}">
                                {{ $method }}
                            </option>
                        @endforeach
                    </select>

                    @error('paymentMethod')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-payment-date">
                        Payment date
                    </label>

                    <input
                        id="aswf-payment-date"
                        type="date"
                        class="aswf-control"
                        wire:model="paymentDate"
                    >

                    @error('paymentDate')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-payment-reference">
                        Transaction reference
                    </label>

                    <input
                        id="aswf-payment-reference"
                        type="text"
                        class="aswf-control"
                        wire:model="paymentReference"
                        maxlength="255"
                    >

                    @error('paymentReference')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field aswf-field--full">
                    <label for="aswf-payment-notes">
                        Payment notes
                    </label>

                    <textarea
                        id="aswf-payment-notes"
                        class="aswf-control"
                        wire:model="paymentNotes"
                    ></textarea>

                    @error('paymentNotes')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <div class="aswf-form-actions">
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    wire:click="closePaymentEditor"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    color="warning"
                    wire:loading.attr="disabled"
                    wire:target="recordPayment"
                >
                    Record and Allocate Payment
                </x-filament::button>
            </div>
        </form>
    @endif

    @if ($creditNoteEditorOpen)
        <form
            id="aswf-credit-note-editor"
            wire:submit="issueCreditNote"
            class="aswf-form"
            x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
        >
            <div class="asw-heading">
                Issue credit note
            </div>

            <div class="asw-muted" style="margin-top: 0.25rem;">
                Reduce the issued invoice using the authoritative
                credit-note service. The invoice and quotation
                ledgers will be recalculated automatically.
            </div>

            <div class="aswf-form-grid" style="margin-top: 1rem;">
                <div class="aswf-field">
                    <label for="aswf-credit-note-amount">
                        Credit amount
                    </label>

                    <input
                        id="aswf-credit-note-amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="aswf-control"
                        wire:model="creditNoteAmount"
                    >

                    @error('creditNoteAmount')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-credit-note-tax">
                        Tax included in credit
                    </label>

                    <input
                        id="aswf-credit-note-tax"
                        type="number"
                        min="0"
                        step="0.01"
                        class="aswf-control"
                        wire:model="creditNoteTax"
                    >

                    @error('creditNoteTax')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-credit-note-date">
                        Issue date
                    </label>

                    <input
                        id="aswf-credit-note-date"
                        type="date"
                        class="aswf-control"
                        wire:model="creditNoteIssueDate"
                    >

                    @error('creditNoteIssueDate')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-credit-note-description">
                        Line description
                    </label>

                    <input
                        id="aswf-credit-note-description"
                        type="text"
                        maxlength="255"
                        class="aswf-control"
                        wire:model="creditNoteDescription"
                    >

                    @error('creditNoteDescription')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field aswf-field--full">
                    <label for="aswf-credit-note-reason">
                        Reason
                    </label>

                    <textarea
                        id="aswf-credit-note-reason"
                        class="aswf-control"
                        wire:model="creditNoteReason"
                    ></textarea>

                    @error('creditNoteReason')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <div class="aswf-form-actions">
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    wire:click="closeCreditNoteEditor"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    color="warning"
                    wire:loading.attr="disabled"
                    wire:target="issueCreditNote"
                    wire:confirm="Issue this credit note? The invoice ledger will be recalculated immediately."
                >
                    Issue Credit Note
                </x-filament::button>
            </div>
        </form>
    @endif

    @if ($refundEditorOpen)
        <form
            id="aswf-refund-editor"
            wire:submit="processRefund"
            class="aswf-form"
            x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
        >
            <div class="asw-heading">
                Process refund
            </div>

            <div class="asw-muted" style="margin-top: 0.25rem;">
                Refund an allocated payment. Linking an issued credit
                note is optional and further limits the refundable amount.
            </div>

            <div class="aswf-form-grid" style="margin-top: 1rem;">
                <div class="aswf-field aswf-field--full">
                    <label for="aswf-refund-payment">
                        Payment
                    </label>

                    <select
                        id="aswf-refund-payment"
                        class="aswf-control"
                        wire:model="refundPaymentId"
                    >
                        <option value="">
                            Select an allocated payment
                        </option>

                        @foreach ($refundPaymentOptions as $paymentId => $label)
                            <option value="{{ $paymentId }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('refundPaymentId')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field aswf-field--full">
                    <label for="aswf-refund-credit-note">
                        Credit note link
                    </label>

                    <select
                        id="aswf-refund-credit-note"
                        class="aswf-control"
                        wire:model="refundCreditNoteId"
                    >
                        <option value="">
                            No linked credit note
                        </option>

                        @foreach ($refundCreditNoteOptions as $creditNoteId => $label)
                            <option value="{{ $creditNoteId }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('refundCreditNoteId')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-refund-amount">
                        Refund amount
                    </label>

                    <input
                        id="aswf-refund-amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="aswf-control"
                        wire:model="refundAmount"
                    >

                    @error('refundAmount')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-refund-method">
                        Refund method
                    </label>

                    <select
                        id="aswf-refund-method"
                        class="aswf-control"
                        wire:model="refundMethod"
                    >
                        @foreach ([
                            'Original Method',
                            'Cash',
                            'UPI',
                            'Bank Transfer',
                            'Cheque',
                            'Credit Card',
                            'Debit Card',
                        ] as $method)
                            <option value="{{ $method }}">
                                {{ $method }}
                            </option>
                        @endforeach
                    </select>

                    @error('refundMethod')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-refund-date">
                        Refund date
                    </label>

                    <input
                        id="aswf-refund-date"
                        type="date"
                        class="aswf-control"
                        wire:model="refundDate"
                    >

                    @error('refundDate')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field">
                    <label for="aswf-refund-reference">
                        Transaction reference
                    </label>

                    <input
                        id="aswf-refund-reference"
                        type="text"
                        maxlength="255"
                        class="aswf-control"
                        wire:model="refundReference"
                    >

                    @error('refundReference')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswf-field aswf-field--full">
                    <label for="aswf-refund-reason">
                        Reason
                    </label>

                    <textarea
                        id="aswf-refund-reason"
                        class="aswf-control"
                        wire:model="refundReason"
                    ></textarea>

                    @error('refundReason')
                        <span class="aswf-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <div class="aswf-form-actions">
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    wire:click="closeRefundEditor"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    color="danger"
                    wire:loading.attr="disabled"
                    wire:target="processRefund"
                    wire:confirm="Process this refund? The invoice ledger will be updated immediately."
                >
                    Process Refund
                </x-filament::button>
            </div>
        </form>
    @endif

    @if ($invoice && $canViewAdjustments)
        <div class="aswf-adjustments">
            <div class="aswf-adjustment-head">
                <div>
                    <div class="asw-heading">
                        Financial adjustments
                    </div>

                    <div class="asw-muted">
                        {{ $adjustments->count() }}
                        adjustment{{ $adjustments->count() === 1
                            ? ''
                            : 's' }}
                    </div>
                </div>
            </div>

            @forelse ($adjustments as $adjustment)
                @php
                    $adjustmentRecord = $adjustment['record'];
                    $isCreditNote = $adjustment['kind'] === 'credit-note';
                @endphp

                <div
                    class="aswf-adjustment"
                    wire:key="sales-finance-adjustment-{{ $adjustment['kind'] }}-{{ $adjustmentRecord->getKey() }}"
                >
                    <div class="aswf-adjustment-head">
                        <div>
                            <div class="aswf-adjustment__title">
                                <span class="aswf-badge {{ $isCreditNote
                                    ? 'aswf-badge--credit'
                                    : 'aswf-badge--refund' }}">
                                    {{ $isCreditNote
                                        ? 'Credit Note'
                                        : 'Refund' }}
                                </span>

                                <span>
                                    {{ $isCreditNote
                                        ? $adjustmentRecord->credit_note_no
                                        : $adjustmentRecord->refund_no }}
                                </span>
                            </div>

                            <div class="aswf-adjustment__meta">
                                {{ $adjustment['occurredAt']?->format(
                                    'd M Y H:i'
                                ) ?: 'No date' }}

                                · {{ $adjustmentRecord->status }}

                                @if (
                                    ! $isCreditNote
                                    && $adjustmentRecord->payment
                                )
                                    · Payment
                                    {{ $adjustmentRecord->payment->payment_no }}
                                @endif

                                @if (
                                    ! $isCreditNote
                                    && $adjustmentRecord->creditNote
                                )
                                    · Credit
                                    {{ $adjustmentRecord->creditNote->credit_note_no }}
                                @endif
                            </div>

                            @if ($adjustmentRecord->reason)
                                <div class="aswf-adjustment__meta">
                                    {{ $adjustmentRecord->reason }}
                                </div>
                            @endif
                        </div>

                        <div class="aswf-adjustment__amount">
                            ₹{{ number_format(
                                (float) ($isCreditNote
                                    ? $adjustmentRecord->grand_total
                                    : $adjustmentRecord->amount),
                                2
                            ) }}
                        </div>
                    </div>

                    <div
                        class="aswf-adjustment-actions"
                        style="margin-top: 0.6rem;"
                    >
                        @can('download', $adjustmentRecord)
                            @if (
                                $isCreditNote
                                && $adjustmentRecord->credit_note_pdf
                            )
                                <a
                                    class="aswf-document-link"
                                    href="{{ route(
                                        'finance.credit-notes.download',
                                        $adjustmentRecord
                                    ) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Download Credit Note
                                </a>
                            @elseif (
                                ! $isCreditNote
                                && $adjustmentRecord->refund_pdf
                            )
                                <a
                                    class="aswf-document-link"
                                    href="{{ route(
                                        'finance.refunds.download',
                                        $adjustmentRecord
                                    ) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Download Refund
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <div class="aswf-empty">
                    No credit notes or refunds have been recorded
                    for this invoice.
                </div>
            @endforelse
        </div>
    @endif

    @if ($quotation)
        <div class="aswf-payments">
            <div class="aswf-payment-head">
                <div>
                    <div class="asw-heading">
                        Payment history
                    </div>

                    <div class="asw-muted">
                        {{ $payments->count() }}
                        payment record{{ $payments->count() === 1
                            ? ''
                            : 's' }}
                    </div>
                </div>
            </div>

            @forelse ($payments as $payment)
                <div
                    class="aswf-payment"
                    wire:key="sales-finance-payment-{{ $payment->getKey() }}"
                >
                    <div class="aswf-payment-head">
                        <div>
                            <div class="aswf-payment__number">
                                {{ $payment->payment_no }}
                            </div>

                            <div class="aswf-payment__meta">
                                {{ $payment->payment_date?->format(
                                    'd M Y'
                                ) ?: 'No date' }}

                                · {{ $payment->payment_method }}

                                @if ($payment->transaction_reference)
                                    · {{ $payment->transaction_reference }}
                                @endif

                                @if (
                                    (float) $payment->refunds
                                        ->where('status', 'Processed')
                                        ->sum('amount') > 0
                                )
                                    · Refunded
                                    ₹{{ number_format(
                                        (float) $payment->refunds
                                            ->where('status', 'Processed')
                                            ->sum('amount'),
                                        2
                                    ) }}
                                @endif
                            </div>
                        </div>

                        <div class="aswf-payment__amount">
                            ₹{{ number_format(
                                (float) $payment->amount,
                                2
                            ) }}
                        </div>
                    </div>

                    <div
                        class="aswf-payment-actions"
                        style="margin-top: 0.6rem;"
                    >
                        @can('downloadReceipt', $payment)
                            @if ($payment->receipt_pdf)
                                <a
                                    class="aswf-document-link"
                                    href="{{ route(
                                        'finance.payments.receipt.download',
                                        $payment
                                    ) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Receipt
                                </a>
                            @endif
                        @endcan

                        @can('downloadStatement', $payment)
                            @if ($payment->statement_pdf)
                                <a
                                    class="aswf-document-link"
                                    href="{{ route(
                                        'finance.payments.statement.download',
                                        $payment
                                    ) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Statement
                                </a>
                            @endif
                        @endcan

                        @if (
                            ! $payment->receipt_pdf
                            && ! $payment->statement_pdf
                        )
                            <span class="asw-muted">
                                No downloadable documents
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="aswf-empty">
                    No payments have been recorded for this
                    quotation.
                </div>
            @endforelse
        </div>
    @endif
</div>