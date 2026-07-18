<x-filament-panels::page>
    <style>
        .asw {
            --asw-border: #e5e7eb;
            --asw-muted: #64748b;
            --asw-surface: #ffffff;
            --asw-soft: #f8fafc;
            --asw-text: #111827;
            --asw-primary: #d97706;
            --asw-primary-soft: #fff7ed;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
        }

        .dark .asw {
            --asw-border: rgba(255, 255, 255, 0.12);
            --asw-muted: #94a3b8;
            --asw-surface: #111827;
            --asw-soft: rgba(255, 255, 255, 0.045);
            --asw-text: #f8fafc;
            --asw-primary-soft: rgba(217, 119, 6, 0.13);
        }

        .asw * {
            box-sizing: border-box;
        }

        .asw-card {
            background: var(--asw-surface);
            border: 1px solid var(--asw-border);
            border-radius: 1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .asw-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .asw-metric {
            padding: 1rem 1.1rem;
        }

        .asw-metric__label,
        .asw-label {
            color: var(--asw-muted);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }

        .asw-metric__value {
            color: var(--asw-text);
            font-size: 1.9rem;
            font-weight: 750;
            line-height: 1.15;
            margin-top: 0.3rem;
        }

        .asw-shell {
            display: grid;
            grid-template-columns: minmax(290px, 340px) minmax(0, 1fr);
            gap: 1.25rem;
            align-items: start;
        }

        .asw-pipeline {
            overflow: hidden;
            position: sticky;
            top: 1rem;
        }

        .asw-pipeline__head,
        .asw-section {
            padding: 1.15rem;
        }

        .asw-pipeline__head {
            border-bottom: 1px solid var(--asw-border);
        }

        .asw-pipeline__heading {
            align-items: flex-start;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .asw-lead-intake {
            scroll-margin-top: 5rem;
        }

        .asw-title {
            color: var(--asw-text);
            font-size: 1rem;
            font-weight: 750;
            margin: 0;
        }

        .asw-subtitle,
        .asw-muted {
            color: var(--asw-muted);
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .asw-subtitle {
            margin: 0.25rem 0 0;
        }

        .asw-controls {
            display: grid;
            gap: 0.65rem;
            margin-top: 1rem;
        }

        .asw-control {
            appearance: none;
            background: var(--asw-surface);
            border: 1px solid var(--asw-border);
            border-radius: 0.7rem;
            color: var(--asw-text);
            display: block;
            font: inherit;
            font-size: 0.86rem;
            min-height: 2.55rem;
            outline: none;
            padding: 0.65rem 0.75rem;
            width: 100%;
        }

        .asw-control:focus {
            border-color: var(--asw-primary);
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.14);
        }

        .asw-pipeline__list {
            max-height: 72vh;
            overflow-y: auto;
        }

        .asw-lead {
            appearance: none;
            background: transparent;
            border: 0;
            border-bottom: 1px solid var(--asw-border);
            color: inherit;
            cursor: pointer;
            display: block;
            padding: 1rem;
            text-align: left;
            transition: background 150ms ease, box-shadow 150ms ease;
            width: 100%;
        }

        .asw-lead:hover {
            background: var(--asw-soft);
        }

        .asw-lead.is-selected {
            background: var(--asw-primary-soft);
            box-shadow: inset 3px 0 0 var(--asw-primary);
        }

        .asw-row {
            align-items: flex-start;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .asw-min {
            min-width: 0;
        }

        .asw-lead__name,
        .asw-heading,
        .asw-money {
            color: var(--asw-text);
            font-weight: 700;
        }

        .asw-lead__name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .asw-lead__meta {
            color: var(--asw-muted);
            font-size: 0.76rem;
            margin-top: 0.3rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .asw-lead__foot {
            align-items: center;
            color: var(--asw-muted);
            display: flex;
            font-size: 0.74rem;
            gap: 0.5rem;
            justify-content: space-between;
            margin-top: 0.75rem;
        }

        .asw-overdue {
            color: #dc2626;
            font-weight: 700;
            margin-top: 0.45rem;
        }

        .asw-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
            padding: 0.42rem 0.58rem;
            white-space: nowrap;
        }

        .asw-badge--new {
            background: #f1f5f9;
            color: #475569;
        }

        .asw-badge--contacted {
            background: #e0f2fe;
            color: #0369a1;
        }

        .asw-badge--qualified {
            background: #ede9fe;
            color: #6d28d9;
        }

        .asw-badge--meeting {
            background: #fef3c7;
            color: #92400e;
        }

        .asw-badge--completed {
            background: #dcfce7;
            color: #15803d;
        }

        .asw-badge--proposal,
        .asw-badge--negotiation {
            background: #ffedd5;
            color: #c2410c;
        }

        .asw-badge--won {
            background: #dcfce7;
            color: #15803d;
        }

        .asw-badge--lost {
            background: #fee2e2;
            color: #b91c1c;
        }

        .dark .asw-badge {
            filter: saturate(0.9) brightness(0.85);
        }

        .asw-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
        }

        .asw-hero {
            padding: 1.25rem;
        }

        .asw-hero__top {
            align-items: flex-start;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .asw-hero__title {
            color: var(--asw-text);
            font-size: 1.55rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            line-height: 1.2;
            margin: 0;
        }

        .asw-hero__badges,
        .asw-actions,
        .asw-inline-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .asw-hero__badges {
            margin-top: 0.65rem;
        }

        .asw-contact {
            color: var(--asw-muted);
            display: flex;
            flex-wrap: wrap;
            font-size: 0.82rem;
            gap: 0.35rem 1rem;
            margin-top: 0.75rem;
        }

        .asw-assignment {
            color: var(--asw-muted);
            font-size: 0.84rem;
            margin-top: 0.8rem;
        }

        .asw-assignment strong {
            color: var(--asw-text);
        }

        .asw-totals {
            display: grid;
            flex: 0 0 auto;
            gap: 0.55rem;
            grid-template-columns: repeat(2, minmax(120px, 1fr));
        }

        .asw-total {
            background: var(--asw-soft);
            border-radius: 0.75rem;
            padding: 0.75rem;
            text-align: right;
        }

        .asw-total__value {
            color: var(--asw-text);
            font-size: 0.95rem;
            font-weight: 750;
            margin-top: 0.2rem;
        }

        .asw-stages {
            display: grid;
            gap: 0.65rem;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .asw-stage {
            background: var(--asw-surface);
            border: 1px solid var(--asw-border);
            border-radius: 0.85rem;
            min-height: 6.5rem;
            padding: 0.85rem;
        }

        .asw-stage.is-completed {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .asw-stage.is-current {
            background: var(--asw-primary-soft);
            border-color: #fdba74;
        }

        .dark .asw-stage.is-completed {
            background: rgba(22, 163, 74, 0.1);
            border-color: rgba(74, 222, 128, 0.35);
        }

        .asw-stage__head {
            align-items: center;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
        }

        .asw-stage__name {
            color: var(--asw-text);
            font-size: 0.82rem;
            font-weight: 750;
        }

        .asw-stage__mark {
            color: var(--asw-primary);
            font-weight: 800;
        }

        .asw-stage.is-completed .asw-stage__mark {
            color: #16a34a;
        }

        .asw-stage__detail {
            color: var(--asw-muted);
            font-size: 0.72rem;
            line-height: 1.45;
            margin-top: 0.5rem;
        }

        .asw-next {
            align-items: center;
            background: var(--asw-primary-soft);
            border: 1px solid #fed7aa;
            border-radius: 1rem;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            padding: 1.15rem;
        }

        .dark .asw-next {
            border-color: rgba(251, 146, 60, 0.35);
        }

        .asw-next__eyebrow {
            color: var(--asw-primary);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .asw-next__title {
            color: var(--asw-text);
            font-size: 1.05rem;
            font-weight: 800;
            margin-top: 0.2rem;
        }

        .asw-grid-2,
        .asw-grid-3,
        .asw-form-grid,
        .asw-count-grid {
            display: grid;
            gap: 1rem;
        }

        .asw-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .asw-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .asw-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 1rem;
        }

        .asw-count-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 1rem;
        }

        .asw-span-2 {
            grid-column: 1 / -1;
        }

        .asw-field {
            display: block;
        }

        .asw-field__label {
            color: var(--asw-text);
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .asw-error {
            color: #dc2626;
            display: block;
            font-size: 0.72rem;
            margin-top: 0.3rem;
        }

        .asw-section__head {
            align-items: center;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }

        .asw-section__title {
            color: var(--asw-text);
            font-size: 1rem;
            font-weight: 800;
            margin: 0;
        }

        .asw-link {
            appearance: none;
            background: transparent;
            border: 0;
            color: var(--asw-primary);
            cursor: pointer;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 750;
            padding: 0;
        }

        .asw-meetings,
        .asw-timeline {
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
            margin-top: 1rem;
        }

        .asw-meeting {
            border: 1px solid var(--asw-border);
            border-radius: 0.75rem;
            padding: 0.85rem;
        }

        .asw-count {
            background: var(--asw-soft);
            border-radius: 0.75rem;
            padding: 0.8rem;
        }

        .asw-count__value {
            color: var(--asw-text);
            font-size: 1.35rem;
            font-weight: 800;
            margin-top: 0.2rem;
        }

        .asw-finance {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            margin-top: 1rem;
        }

        .asw-finance__row {
            align-items: center;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
        }

        .asw-finance__row.is-total {
            border-top: 1px solid var(--asw-border);
            font-weight: 800;
            margin-top: 0.25rem;
            padding-top: 0.65rem;
        }

        .asw-summary-value {
            color: var(--asw-text);
            font-size: 1.15rem;
            font-weight: 800;
            margin-top: 0.8rem;
        }

        .asw-payment-row {
            align-items: center;
            display: flex;
            font-size: 0.82rem;
            gap: 0.75rem;
            justify-content: space-between;
            margin-top: 0.65rem;
        }

        .asw-timeline__item {
            display: grid;
            gap: 0.7rem;
            grid-template-columns: 0.7rem minmax(0, 1fr);
        }

        .asw-timeline__dot {
            background: var(--asw-primary);
            border-radius: 999px;
            height: 0.6rem;
            margin-top: 0.32rem;
            width: 0.6rem;
        }

        .asw-timeline__title {
            color: var(--asw-text);
            font-size: 0.84rem;
            font-weight: 750;
        }

        .asw-timeline__date {
            color: var(--asw-muted);
            font-size: 0.7rem;
            margin-top: 0.25rem;
        }

        .asw-empty {
            padding: 3rem 1.25rem;
            text-align: center;
        }

        @media (max-width: 1200px) {
            .asw-stages {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .asw-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .asw-shell,
            .asw-grid-2,
            .asw-grid-3 {
                grid-template-columns: minmax(0, 1fr);
            }

            .asw-pipeline {
                position: static;
            }

            .asw-pipeline__list {
                max-height: 24rem;
            }

            .asw-hero__top,
            .asw-next {
                align-items: stretch;
                flex-direction: column;
            }

            .asw-totals {
                width: 100%;
            }
        }

        @media (max-width: 640px) {
            .asw-metrics,
            .asw-stages,
            .asw-form-grid,
            .asw-count-grid,
            .asw-totals {
                grid-template-columns: minmax(0, 1fr);
            }

            .asw-total {
                text-align: left;
            }
        }
    </style>

    @php
        $statusClasses = [
            'New' => 'asw-badge--new',
            'Contacted' => 'asw-badge--contacted',
            'Qualified' => 'asw-badge--qualified',
            'Meeting Scheduled' => 'asw-badge--meeting',
            'Meeting Completed' => 'asw-badge--completed',
            'Proposal Sent' => 'asw-badge--proposal',
            'Negotiation' => 'asw-badge--negotiation',
            'Won' => 'asw-badge--won',
            'Lost' => 'asw-badge--lost',
        ];
    @endphp

    <div class="asw">
        <section class="asw-metrics">
            @foreach ([
                ['label' => 'Active pipeline', 'value' => $pipelineCounts['all'] ?? 0],
                ['label' => 'Qualified', 'value' => $pipelineCounts['Qualified'] ?? 0],
                ['label' => 'Meetings', 'value' => $pipelineCounts['Meeting Scheduled'] ?? 0],
                ['label' => 'Won', 'value' => $pipelineCounts['Won'] ?? 0],
            ] as $metric)
                <div class="asw-card asw-metric">
                    <div class="asw-metric__label">{{ $metric['label'] }}</div>
                    <div class="asw-metric__value">{{ $metric['value'] }}</div>
                </div>
            @endforeach
        </section>

        @if ($showLeadForm)
            <section
                id="asw-lead-intake"
                class="asw-card asw-section asw-lead-intake"
                x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
            >
                <div class="asw-section__head">
                    <div>
                        <h2 class="asw-section__title">
                            Add a lead
                        </h2>
                        <div class="asw-muted">
                            The customer will be created or reused automatically.
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="closeLeadForm"
                        class="asw-link"
                    >
                        Close
                    </button>
                </div>

                <form
                    wire:submit="createLead"
                    class="asw-form-grid"
                >
                    <label class="asw-field">
                        <span class="asw-field__label">
                            Company name
                        </span>
                        <input
                            type="text"
                            wire:model="leadCompanyName"
                            class="asw-control"
                        />
                        @error('leadCompanyName')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Contact person *
                        </span>
                        <input
                            type="text"
                            wire:model="leadContactPerson"
                            class="asw-control"
                            required
                        />
                        @error('leadContactPerson')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Designation
                        </span>
                        <select
                            wire:model="leadDesignation"
                            class="asw-control"
                        >
                            <option value="">
                                Select designation
                            </option>

                            @foreach ($leadDesignationOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('leadDesignation')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Email *
                        </span>
                        <input
                            type="email"
                            wire:model="leadEmail"
                            class="asw-control"
                            required
                        />
                        @error('leadEmail')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Phone *
                        </span>
                        <input
                            type="tel"
                            wire:model="leadPhone"
                            class="asw-control"
                            required
                        />
                        @error('leadPhone')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            WhatsApp
                        </span>
                        <input
                            type="tel"
                            wire:model="leadWhatsapp"
                            class="asw-control"
                        />
                        @error('leadWhatsapp')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Priority
                        </span>
                        <select
                            wire:model="leadPriority"
                            class="asw-control"
                        >
                            @foreach (['Low', 'Medium', 'High', 'Urgent'] as $priority)
                                <option value="{{ $priority }}">
                                    {{ $priority }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Lead source
                        </span>
                        <select
                            wire:model="leadSource"
                            class="asw-control"
                        >
                            <option value="">
                                Select lead source
                            </option>

                            @foreach ($leadSourceOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('leadSource')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Estimated value
                        </span>
                        <select
                            wire:model="leadEstimatedValue"
                            class="asw-control"
                        >
                            @foreach ($leadEstimatedValueOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('leadEstimatedValue')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Industry
                        </span>
                        <select
                            wire:model="leadIndustry"
                            class="asw-control"
                        >
                            <option value="">
                                Select industry
                            </option>

                            @foreach ($leadIndustryOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('leadIndustry')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Business type
                        </span>
                        <select
                            wire:model="leadBusinessType"
                            class="asw-control"
                        >
                            <option value="">
                                Select business type
                            </option>

                            @foreach ($leadBusinessTypeOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('leadBusinessType')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field">
                        <span class="asw-field__label">
                            Company size
                        </span>
                        <select
                            wire:model="leadCompanySize"
                            class="asw-control"
                        >
                            <option value="">
                                Select company size
                            </option>

                            @foreach ($leadCompanySizeOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('leadCompanySize')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field asw-span-2">
                        <span class="asw-field__label">
                            Assigned employee
                        </span>

                        @if ($canManageLeadAssignments)
                            <select
                                wire:model.number="leadAssignedEmployeeId"
                                class="asw-control"
                            >
                                <option value="">Unassigned</option>

                                @foreach ($leadEmployees as $employee)
                                    <option value="{{ $employee->getKey() }}">
                                        {{ $employee->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input
                                type="text"
                                value="{{ $leadAssignmentEmployee?->full_name ?: 'No active employee linked' }}"
                                class="asw-control"
                                readonly
                            />

                            <div
                                class="asw-muted"
                                style="margin-top: 0.35rem;"
                            >
                                @if ($leadAssignmentEmployee)
                                    Assigned to your employee profile.
                                @else
                                    Ask an administrator to link and activate your employee profile.
                                @endif
                            </div>
                        @endif

                        @error('leadAssignedEmployeeId')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="asw-field asw-span-2">
                        <span class="asw-field__label">
                            Requirements summary
                        </span>
                        <textarea
                            wire:model="leadRequirementsSummary"
                            rows="4"
                            class="asw-control"
                        ></textarea>
                        @error('leadRequirementsSummary')
                            <span class="asw-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <div
                        class="asw-span-2 asw-actions"
                        style="justify-content: flex-end;"
                    >
                        <x-filament::button
                            type="button"
                            color="gray"
                            outlined
                            wire:click="closeLeadForm"
                            wire:loading.attr="disabled"
                            wire:target="createLead"
                        >
                            Cancel
                        </x-filament::button>

                        <x-filament::button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="createLead"
                        >
                            <span
                                wire:loading.remove
                                wire:target="createLead"
                            >
                                Create Lead
                            </span>

                            <span
                                wire:loading
                                wire:target="createLead"
                            >
                                Creating...
                            </span>
                        </x-filament::button>
                    </div>
                </form>
            </section>
        @endif

        <div class="asw-shell">
            <aside class="asw-card asw-pipeline">
                <div class="asw-pipeline__head">
                    <div class="asw-pipeline__heading">
                        <div>
                            <h2 class="asw-title">
                                Sales pipeline
                            </h2>
                            <p class="asw-subtitle">
                                Select a lead and continue the complete journey here.
                            </p>
                        </div>

                        @if ($canCreateLead)
                            <x-filament::button
                                type="button"
                                size="sm"
                                wire:click="openLeadForm"
                                wire:loading.attr="disabled"
                                wire:target="openLeadForm"
                            >
                                + Add Lead
                            </x-filament::button>
                        @endif
                    </div>

                    <div class="asw-controls">
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search lead, company, email or phone"
                            class="asw-control"
                        />

                        <select wire:model.live="status" class="asw-control">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">
                                    {{ $label }} ({{ $pipelineCounts[$value] ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="asw-pipeline__list">
                    @forelse ($leads as $lead)
                        <button
                            type="button"
                            wire:click="selectLead({{ $lead->getKey() }})"
                            wire:key="sales-lead-{{ $lead->getKey() }}"
                            class="asw-lead {{ $selectedLead?->is($lead) ? 'is-selected' : '' }}"
                        >
                            <div class="asw-row">
                                <div class="asw-min">
                                    <div class="asw-lead__name">
                                        {{ $lead->company_name ?: $lead->contact_person }}
                                    </div>
                                    <div class="asw-lead__meta">
                                        {{ $lead->lead_code }} · {{ $lead->contact_person }}
                                    </div>
                                </div>

                                @php
                                    $displayLeadStatus =
                                        $leadDisplayStatuses[
                                            $lead->getKey()
                                        ]
                                        ?? $lead->lead_status;
                                @endphp

                                <span class="asw-badge {{ $statusClasses[$displayLeadStatus] ?? 'asw-badge--new' }}">
                                    {{ $displayLeadStatus }}
                                </span>
                            </div>

                            <div class="asw-lead__foot">
                                <span>{{ $lead->assignedEmployee?->full_name ?: 'Unassigned' }}</span>
                                <span>₹{{ number_format((float) $lead->estimated_value, 0) }}</span>
                            </div>

                            @if ($lead->next_follow_up_date)
                                <div class="asw-lead__meta {{ $lead->next_follow_up_date->isPast() ? 'asw-overdue' : '' }}">
                                    Follow-up {{ $lead->next_follow_up_date->format('d M Y') }}
                                </div>
                            @endif
                        </button>
                    @empty
                        <div class="asw-empty asw-muted">
                            No leads match the current filters.
                        </div>
                    @endforelse
                </div>
            </aside>

            <main class="asw-content">
                @if ($selectedLead)
                    <section class="asw-card asw-hero">
                        <div class="asw-hero__top">
                            <div class="asw-min">
                                <h1 class="asw-hero__title">
                                    {{ $selectedLead->company_name ?: $selectedLead->contact_person }}
                                </h1>

                                <div class="asw-hero__badges">
                                    <span class="asw-badge {{ $statusClasses[$selectedLeadDisplayStatus] ?? 'asw-badge--new' }}">
                                        {{ $selectedLeadDisplayStatus }}
                                    </span>
                                    <span class="asw-badge asw-badge--new">
                                        {{ $selectedLead->priority }} priority
                                    </span>
                                </div>

                                <div class="asw-contact">
                                    <span>{{ $selectedLead->lead_code }}</span>
                                    <span>{{ $selectedLead->contact_person }}</span>
                                    <span>{{ $selectedLead->phone }}</span>
                                    <span>{{ $selectedLead->email }}</span>
                                </div>

                                <div class="asw-assignment">
                                    Assigned to
                                    <strong>
                                        {{ $selectedLead->assignedEmployee?->full_name ?: 'Unassigned' }}
                                    </strong>
                                    @if ($selectedLead->convertedCustomer)
                                        · Customer {{ $selectedLead->convertedCustomer->customer_code }}
                                    @endif
                                </div>
                            </div>

                            <div class="asw-totals">
                                <div class="asw-total">
                                    <div class="asw-label">Estimated value</div>
                                    <div class="asw-total__value">
                                        ₹{{ number_format((float) $selectedLead->estimated_value, 2) }}
                                    </div>
                                </div>
                                <div class="asw-total">
                                    <div class="asw-label">Balance</div>
                                    <div class="asw-total__value">
                                        ₹{{ number_format($summary['balance_total'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="asw-stages">
                        @foreach ($stages as $stage)
                            <div class="asw-stage is-{{ $stage['state'] }}">
                                <div class="asw-stage__head">
                                    <div class="asw-stage__name">{{ $stage['label'] }}</div>
                                    <div class="asw-stage__mark">
                                        {{ $stage['state'] === 'completed' ? '✓' : ($stage['state'] === 'current' ? '●' : '○') }}
                                    </div>
                                </div>
                                <div class="asw-stage__detail">{{ $stage['detail'] }}</div>
                            </div>
                        @endforeach
                    </section>

                    <section class="asw-next">
                        <div>
                            <div class="asw-next__eyebrow">Recommended next action</div>
                            <div class="asw-next__title">{{ $nextAction['label'] }}</div>
                            <div class="asw-muted">{{ $nextAction['description'] }}</div>
                        </div>

                        <div class="asw-actions">
                            @if ($nextAction['key'] === 'mark-contacted')
                                <x-filament::button wire:click="transitionLead('Contacted')" wire:loading.attr="disabled">
                                    Mark Contacted
                                </x-filament::button>
                            @elseif ($nextAction['key'] === 'qualify-lead')
                                <x-filament::button wire:click="transitionLead('Qualified')" wire:loading.attr="disabled">
                                    Mark Qualified
                                </x-filament::button>
                            @elseif ($nextAction['key'] === 'schedule-meeting')
                                <x-filament::button wire:click="openMeetingForm" wire:loading.attr="disabled">
                                    Schedule Meeting
                                </x-filament::button>
                            @elseif ($nextAction['key'] === 'create-quotation')
                                <x-filament::button
                                    type="button"
                                    x-on:click="document.getElementById('asw-quotation-workspace')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                                >
                                    Build Quotation Here
                                </x-filament::button>
                            @endif

                            @if (! in_array($selectedLead->lead_status, ['Won', 'Lost'], true))
                                <x-filament::button
                                    color="danger"
                                    outlined
                                    wire:click="transitionLead('Lost')"
                                    wire:confirm="Mark this lead as lost? This terminal status cannot move backward automatically."
                                    wire:loading.attr="disabled"
                                >
                                    Mark Lost
                                </x-filament::button>
                            @endif
                        </div>
                    </section>

                    <livewire:sales-workspace-quotation
                        :lead-id="$selectedLead->getKey()"
                        :key="'sales-workspace-quotation-'.$selectedLead->getKey()"
                    />

                    <livewire:sales-workspace-finance
                        :lead-id="$selectedLead->getKey()"
                        :key="'sales-workspace-finance-'.$selectedLead->getKey()"
                    />
                    @if ($showMeetingForm)
                        <section class="asw-card asw-section">
                            <div class="asw-section__head">
                                <div>
                                    <h2 class="asw-section__title">Schedule meeting</h2>
                                    <div class="asw-muted">
                                        Lead, customer, and assigned employee are inherited automatically.
                                    </div>
                                </div>
                                <button type="button" wire:click="closeMeetingForm" class="asw-link">
                                    Close
                                </button>
                            </div>

                            <form wire:submit="scheduleMeeting" class="asw-form-grid">
                                <label class="asw-field asw-span-2">
                                    <span class="asw-field__label">Meeting title</span>
                                    <input type="text" wire:model="meetingTitle" class="asw-control" />
                                    @error('meetingTitle')
                                        <span class="asw-error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="asw-field">
                                    <span class="asw-field__label">Type</span>
                                    <select wire:model="meetingType" class="asw-control">
                                        @foreach (['Office', 'Client Site', 'Online', 'Phone'] as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="asw-field">
                                    <span class="asw-field__label">Duration</span>
                                    <input type="number" min="0" max="1440" wire:model="meetingDuration" class="asw-control" />
                                </label>

                                <label class="asw-field">
                                    <span class="asw-field__label">Date</span>
                                    <input type="date" wire:model="meetingDate" class="asw-control" />
                                    @error('meetingDate')
                                        <span class="asw-error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="asw-field">
                                    <span class="asw-field__label">Time</span>
                                    <input type="time" wire:model="meetingTime" class="asw-control" />
                                    @error('meetingTime')
                                        <span class="asw-error">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="asw-field asw-span-2">
                                    <span class="asw-field__label">Notes</span>
                                    <textarea wire:model="meetingNotes" rows="3" class="asw-control"></textarea>
                                </label>

                                <div class="asw-span-2 asw-actions" style="justify-content: flex-end;">
                                    <x-filament::button type="button" color="gray" outlined wire:click="closeMeetingForm">
                                        Cancel
                                    </x-filament::button>
                                    <x-filament::button type="submit">
                                        Schedule and Sync
                                    </x-filament::button>
                                </div>
                            </form>
                        </section>
                    @endif

                    <section class="asw-grid-2">
                        <div class="asw-card asw-section">
                            <div class="asw-section__head">
                                <h2 class="asw-section__title">Meetings</h2>
                                @if (! in_array($selectedLead->lead_status, ['Won', 'Lost'], true))
                                    <button type="button" wire:click="openMeetingForm" class="asw-link">
                                        Add meeting
                                    </button>
                                @endif
                            </div>

                            <div class="asw-meetings">
                                @forelse ($selectedLead->meetings->sortByDesc('id') as $meeting)
                                    <div class="asw-meeting">
                                        <div class="asw-row">
                                            <div>
                                                <div class="asw-heading">{{ $meeting->meeting_title }}</div>
                                                <div class="asw-muted">
                                                    {{ $meeting->meeting_date?->format('d M Y') }}
                                                    · {{ $meeting->meeting_time }}
                                                    · {{ $meeting->meeting_type }}
                                                </div>
                                            </div>
                                            <span class="asw-badge asw-badge--new">{{ $meeting->status }}</span>
                                        </div>

                                        <div class="asw-muted" style="margin-top: 0.55rem;">
                                            Outcome: {{ $meeting->outcome }}
                                        </div>

                                        @if (in_array($meeting->status, ['Scheduled', 'Confirmed', 'Rescheduled'], true))
                                            <div class="asw-inline-actions" style="margin-top: 0.75rem;">
                                                @foreach (['Interested', 'Follow-up Required', 'Quotation Required', 'Not Interested'] as $outcome)
                                                    <button
                                                        type="button"
                                                        wire:click='completeMeeting({{ $meeting->getKey() }}, @js($outcome))'
                                                        wire:loading.attr="disabled"
                                                        wire:target="completeMeeting"
                                                        wire:confirm="Complete this meeting as {{ $outcome }}?"
                                                        class="asw-control"
                                                        style="min-height: auto; width: auto; padding: 0.45rem 0.6rem; cursor: pointer;"
                                                    >
                                                        {{ $outcome }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="asw-muted">No meetings have been created.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="asw-card asw-section">
                            <h2 class="asw-section__title">Commercial summary</h2>

                            <dl class="asw-count-grid">
                                @foreach ([
                                    ['label' => 'Meetings', 'value' => $summary['meetings']],
                                    ['label' => 'Quotations', 'value' => $summary['quotations']],
                                    ['label' => 'Invoices', 'value' => $summary['invoices']],
                                    ['label' => 'Payments', 'value' => $summary['payments']],
                                ] as $item)
                                    <div class="asw-count">
                                        <dt class="asw-label">{{ $item['label'] }}</dt>
                                        <dd class="asw-count__value">{{ $item['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <dl class="asw-finance">
                                <div class="asw-finance__row">
                                    <dt class="asw-muted">Quotation value</dt>
                                    <dd class="asw-money">₹{{ number_format($summary['quotation_total'], 2) }}</dd>
                                </div>
                                <div class="asw-finance__row">
                                    <dt class="asw-muted">Net payments</dt>
                                    <dd class="asw-money">₹{{ number_format($summary['paid_total'], 2) }}</dd>
                                </div>
                                <div class="asw-finance__row is-total">
                                    <dt>Finance balance</dt>
                                    <dd>₹{{ number_format($summary['balance_total'], 2) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </section>

                    <section class="asw-grid-3">
                        <div class="asw-card asw-section">
                            <h2 class="asw-section__title">Latest quotation</h2>
                            @if ($latestQuotation)
                                <div class="asw-summary-value">{{ $latestQuotation->quotation_code }}</div>
                                <div class="asw-muted">
                                    {{ $latestQuotation->status }} · {{ $latestQuotation->payment_status }}
                                </div>
                                <div class="asw-money" style="margin-top: 0.65rem;">
                                    ₹{{ number_format((float) $latestQuotation->grand_total, 2) }}
                                </div>
                            @else
                                <div class="asw-muted" style="margin-top: 0.8rem;">No quotation yet.</div>
                            @endif
                        </div>

                        <div class="asw-card asw-section">
                            <h2 class="asw-section__title">Latest invoice</h2>
                            @if ($latestInvoice)
                                <div class="asw-summary-value">{{ $latestInvoice->invoice_no }}</div>
                                <div class="asw-muted">{{ $latestInvoice->status }}</div>
                                <div class="asw-money" style="margin-top: 0.65rem;">
                                    Balance ₹{{ number_format((float) $latestInvoice->balance_due, 2) }}
                                </div>
                            @else
                                <div class="asw-muted" style="margin-top: 0.8rem;">No invoice yet.</div>
                            @endif
                        </div>

                        <div class="asw-card asw-section">
                            <h2 class="asw-section__title">Payments</h2>
                            @forelse ($payments->sortByDesc('id')->take(3) as $payment)
                                <div class="asw-payment-row">
                                    <span class="asw-muted">{{ $payment->payment_no }}</span>
                                    <span class="asw-money">₹{{ number_format((float) $payment->amount, 2) }}</span>
                                </div>
                            @empty
                                <div class="asw-muted" style="margin-top: 0.8rem;">No payments received.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="asw-card asw-section">
                        <h2 class="asw-section__title">Unified timeline</h2>
                        <div class="asw-timeline">
                            @forelse ($timeline as $event)
                                <div class="asw-timeline__item">
                                    <div class="asw-timeline__dot"></div>
                                    <div>
                                        <div class="asw-timeline__title">{{ $event['title'] }}</div>
                                        <div class="asw-muted">{{ $event['detail'] }}</div>
                                        <div class="asw-timeline__date">
                                            {{ $event['at']?->format('d M Y, h:i A') ?: 'Date unavailable' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="asw-muted">No journey events available.</div>
                            @endforelse
                        </div>
                    </section>
                @else
                    <section class="asw-card asw-empty">
                        <h2 class="asw-section__title">No lead selected</h2>
                        <p class="asw-subtitle">
                            Create or assign a lead to begin the unified sales journey.
                        </p>
                    </section>
                @endif
            </main>
        </div>
    </div>
</x-filament-panels::page>
