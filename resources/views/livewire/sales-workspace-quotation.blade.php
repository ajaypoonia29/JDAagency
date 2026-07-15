<div id="asw-quotation-workspace" class="asw-card asw-section aswq">
    <style>
        .aswq {
            scroll-margin-top: 1rem;
        }

        .aswq-head,
        .aswq-actions,
        .aswq-summary-row {
            align-items: center;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .aswq-actions {
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .aswq-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 1rem;
        }

        .aswq-lines {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-top: 1rem;
        }

        .aswq-line {
            border: 1px solid var(--asw-border);
            border-radius: 0.85rem;
            padding: 0.9rem;
        }

        .aswq-line-grid {
            display: grid;
            gap: 0.7rem;
            grid-template-columns:
                minmax(170px, 1.3fr)
                minmax(190px, 1.5fr)
                repeat(4, minmax(90px, 0.7fr));
        }

        .aswq-field label {
            color: var(--asw-muted);
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }

        .aswq-control {
            background: var(--asw-surface);
            border: 1px solid var(--asw-border);
            border-radius: 0.65rem;
            color: var(--asw-text);
            font: inherit;
            font-size: 0.82rem;
            min-height: 2.45rem;
            padding: 0.55rem 0.65rem;
            width: 100%;
        }

        textarea.aswq-control {
            min-height: 5rem;
            resize: vertical;
        }

        .aswq-error {
            color: #dc2626;
            display: block;
            font-size: 0.7rem;
            margin-top: 0.3rem;
        }

        .aswq-summary {
            background: var(--asw-soft);
            border-radius: 0.85rem;
            display: grid;
            gap: 0.6rem;
            margin-top: 1rem;
            padding: 1rem;
        }

        .aswq-summary-row {
            color: var(--asw-muted);
            font-size: 0.85rem;
        }

        .aswq-summary-row strong {
            color: var(--asw-text);
        }

        .aswq-summary-row.is-total {
            border-top: 1px solid var(--asw-border);
            color: var(--asw-text);
            font-size: 1rem;
            font-weight: 800;
            padding-top: 0.65rem;
        }

        .aswq-items {
            display: grid;
            gap: 0.7rem;
            margin-top: 1rem;
        }

        .aswq-item {
            border-bottom: 1px solid var(--asw-border);
            display: grid;
            gap: 0.75rem;
            grid-template-columns: minmax(0, 1fr) auto;
            padding-bottom: 0.7rem;
        }

        .aswq-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        @media (max-width: 1100px) {
            .aswq-line-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .aswq-head {
                align-items: stretch;
                flex-direction: column;
            }

            .aswq-actions {
                justify-content: flex-start;
            }

            .aswq-grid,
            .aswq-line-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>

    <div class="aswq-head">
        <div>
            <h2 class="asw-section__title">
                Quotation workspace
            </h2>

            <div class="asw-muted">
                Build, approve, send and download the quotation
                without leaving the sales journey.
            </div>
        </div>

        <div class="aswq-actions">
            @if (! $quotation && $qualifyingMeeting && $canCreate)
                <x-filament::button wire:click="openEditor">
                    Build Quotation
                </x-filament::button>
            @endif

            @if (
                $quotation
                && $quotation->status === 'Draft'
                && $canUpdate
            )
                <x-filament::button
                    color="gray"
                    outlined
                    wire:click="openEditor"
                >
                    Edit Draft
                </x-filament::button>
            @endif

            @if (
                $quotation
                && $quotation->status === 'Draft'
                && $canApprove
            )
                <x-filament::button
                    color="success"
                    wire:click="approveQuotation"
                    wire:confirm="Approve this quotation?"
                >
                    Approve
                </x-filament::button>
            @endif

            @if (
                $quotation
                && in_array(
                    $quotation->status,
                    ['Approved', 'Sent', 'Accepted', 'Completed'],
                    true
                )
                && $canSend
            )
                <x-filament::button
                    color="info"
                    wire:click="sendQuotation"
                    wire:confirm="Email this quotation to the customer's primary email address?"
                >
                    {{ $quotation->quotation_send_count > 0
                        ? 'Resend Email'
                        : 'Send Email' }}
                </x-filament::button>
            @endif

            @if ($quotation && $canDownload)
                <x-filament::button
                    color="gray"
                    outlined
                    wire:click="downloadQuotation"
                >
                    Download PDF
                </x-filament::button>
            @endif
        </div>
    </div>

    @if (! $quotation && ! $qualifyingMeeting)
        <div class="asw-muted" style="margin-top: 1rem;">
            Complete a meeting with the outcome
            <strong>Quotation Required</strong>
            before creating a quotation.
        </div>
    @endif

    @if ($quotation)
        <div class="aswq-summary">
            <div class="aswq-summary-row">
                <span>Quotation</span>
                <strong>{{ $quotation->quotation_code }}</strong>
            </div>

            <div class="aswq-summary-row">
                <span>Status</span>
                <strong>{{ $quotation->status }}</strong>
            </div>

            <div class="aswq-summary-row">
                <span>Payment</span>
                <strong>{{ $quotation->payment_status }}</strong>
            </div>

            <div class="aswq-summary-row">
                <span>Last recipient</span>
                <strong>
                    {{ $quotation->last_sent_to ?: 'Not sent' }}
                </strong>
            </div>

            <div class="aswq-summary-row">
                <span>Email attempts</span>
                <strong>
                    {{ (int) $quotation->quotation_send_count }}
                </strong>
            </div>

            <div class="aswq-summary-row is-total">
                <span>Grand total</span>
                <span>
                    ₹{{ number_format(
                        (float) $quotation->grand_total,
                        2
                    ) }}
                </span>
            </div>
        </div>
    @endif

    @if ($quotation && ! $editorOpen)
        <div class="aswq-items">
            @forelse (
                $quotation->items->sortBy('sort_order')
                as $item
            )
                <div class="aswq-item">
                    <div>
                        <div class="asw-heading">
                            {{ $item->service?->service_name
                                ?: $item->description }}
                        </div>

                        <div class="asw-muted">
                            {{ $item->description }}
                        </div>

                        <div class="asw-muted">
                            {{ number_format(
                                (float) $item->quantity,
                                2
                            ) }}
                            × ₹{{ number_format(
                                (float) $item->unit_price,
                                2
                            ) }}
                            · Discount ₹{{ number_format(
                                (float) $item->discount,
                                2
                            ) }}
                        </div>
                    </div>

                    <div class="asw-money">
                        ₹{{ number_format(
                            (float) $item->line_total,
                            2
                        ) }}
                    </div>
                </div>
            @empty
                <div class="asw-muted">
                    No quotation items are available.
                </div>
            @endforelse
        </div>
    @endif

    @if ($editorOpen)
        <form wire:submit="saveDraft">
            <div class="aswq-grid">
                <div class="aswq-field">
                    <label>Quotation date</label>

                    <input
                        type="date"
                        class="aswq-control"
                        wire:model="quotationDate"
                    />

                    @error('quotation_date')
                        <span class="aswq-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswq-field">
                    <label>Valid until</label>

                    <input
                        type="date"
                        class="aswq-control"
                        wire:model="validUntil"
                    />

                    @error('valid_until')
                        <span class="aswq-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <div class="aswq-lines">
                @foreach ($items as $index => $item)
                    <div
                        class="aswq-line"
                        wire:key="quotation-line-{{ $index }}"
                    >
                        <div class="aswq-line-grid">
                            <div class="aswq-field">
                                <label>Service</label>

                                <select
                                    class="aswq-control"
                                    wire:model.live="items.{{ $index }}.service_id"
                                    wire:change="selectService({{ $index }})"
                                >
                                    <option value="">
                                        Select service
                                    </option>

                                    @foreach (
                                        $serviceOptions
                                        as $service
                                    )
                                        <option
                                            value="{{ $service['id'] }}"
                                        >
                                            {{ $service['name'] }}
                                        </option>
                                    @endforeach
                                </select>

                                @error("items.$index.service_id")
                                    <span class="aswq-error">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="aswq-field">
                                <label>Description</label>

                                <input
                                    type="text"
                                    class="aswq-control"
                                    wire:model="items.{{ $index }}.description"
                                />

                                @error("items.$index.description")
                                    <span class="aswq-error">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="aswq-field">
                                <label>Quantity</label>

                                <input
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    class="aswq-control"
                                    wire:model.live.debounce.250ms="items.{{ $index }}.quantity"
                                />
                            </div>

                            <div class="aswq-field">
                                <label>Unit price</label>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="aswq-control"
                                    wire:model.live.debounce.250ms="items.{{ $index }}.unit_price"
                                />
                            </div>

                            <div class="aswq-field">
                                <label>Line discount</label>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="aswq-control"
                                    wire:model.live.debounce.250ms="items.{{ $index }}.discount"
                                />

                                @error("items.$index.discount")
                                    <span class="aswq-error">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <div class="aswq-field">
                                <label>Line total</label>

                                <input
                                    type="text"
                                    readonly
                                    class="aswq-control"
                                    value="₹{{ number_format(
                                        (float) (
                                            $item['line_total']
                                            ?? 0
                                        ),
                                        2
                                    ) }}"
                                />
                            </div>
                        </div>

                        <div
                            class="aswq-actions"
                            style="margin-top: 0.65rem;"
                        >
                            <button
                                type="button"
                                class="asw-link"
                                wire:click="removeItem({{ $index }})"
                            >
                                Remove line
                            </button>
                        </div>

                        @error("items.$index.line_total")
                            <span class="aswq-error">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>
                @endforeach
            </div>

            <button
                type="button"
                class="asw-link"
                style="margin-top: 0.85rem;"
                wire:click="addItem"
            >
                + Add another service
            </button>

            @error('items')
                <span class="aswq-error">
                    {{ $message }}
                </span>
            @enderror

            <div class="aswq-grid">
                <div class="aswq-field">
                    <label>Discount type</label>

                    <select
                        class="aswq-control"
                        wire:model.live="discountType"
                    >
                        <option value="fixed">
                            Fixed amount
                        </option>

                        <option value="percentage">
                            Percentage
                        </option>
                    </select>
                </div>

                <div class="aswq-field">
                    <label>Quotation discount</label>

                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        class="aswq-control"
                        wire:model.live.debounce.250ms="discountValue"
                    />

                    @error('discount_value')
                        <span class="aswq-error">
                            {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="aswq-field">
                    <label>
                        <input
                            type="checkbox"
                            wire:model.live="taxApplicable"
                        />
                        Apply GST
                    </label>
                </div>

                <div class="aswq-field">
                    <label>GST percentage</label>

                    <input
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        class="aswq-control"
                        wire:model.live.debounce.250ms="taxPercentage"
                    />
                </div>

                <div class="aswq-field">
                    <label>Customer notes</label>

                    <textarea
                        class="aswq-control"
                        wire:model="customerNotes"
                    ></textarea>
                </div>

                <div class="aswq-field">
                    <label>Internal notes</label>

                    <textarea
                        class="aswq-control"
                        wire:model="internalNotes"
                    ></textarea>
                </div>
            </div>

            <div class="aswq-summary">
                <div class="aswq-summary-row">
                    <span>Subtotal</span>
                    <strong>
                        ₹{{ number_format(
                            $totals['subtotal'],
                            2
                        ) }}
                    </strong>
                </div>

                <div class="aswq-summary-row">
                    <span>Quotation discount</span>
                    <strong>
                        ₹{{ number_format(
                            $totals['discount_amount'],
                            2
                        ) }}
                    </strong>
                </div>

                <div class="aswq-summary-row">
                    <span>GST</span>
                    <strong>
                        ₹{{ number_format(
                            $totals['tax'],
                            2
                        ) }}
                    </strong>
                </div>

                <div class="aswq-summary-row is-total">
                    <span>Grand total</span>
                    <span>
                        ₹{{ number_format(
                            $totals['grand_total'],
                            2
                        ) }}
                    </span>
                </div>
            </div>

            <div
                class="aswq-actions"
                style="margin-top: 1rem;"
            >
                <x-filament::button
                    type="button"
                    color="gray"
                    outlined
                    wire:click="closeEditor"
                >
                    Cancel
                </x-filament::button>

                <x-filament::button type="submit">
                    Save Draft
                </x-filament::button>
            </div>
        </form>
    @endif
</div>