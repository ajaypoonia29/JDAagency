<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Quotation;
use App\Models\Service;
use App\Services\CRM\SalesWorkspaceQuotationService;
use App\Services\QuotationPdfService;
use App\Support\QuotationCalculator;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class SalesWorkspaceQuotation extends Component
{
    #[Locked]
    public int $leadId;

    public ?int $quotationId = null;

    public bool $editorOpen = false;

    public string $quotationDate = '';

    public string $validUntil = '';

    public string $discountType = 'fixed';

    public string $discountValue = '0';

    public bool $taxApplicable = false;

    public string $taxPercentage = '18';

    public string $customerNotes = '';

    public string $internalNotes = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $items = [];

    /**
     * @var array{
     *     subtotal: float,
     *     discount_amount: float,
     *     tax: float,
     *     grand_total: float
     * }
     */
    public array $totals = [
        'subtotal' => 0,
        'discount_amount' => 0,
        'tax' => 0,
        'grand_total' => 0,
    ];

    public function mount(int $leadId): void
    {
        $this->leadId = $leadId;

        $lead = $this->lead();
        Gate::authorize('view', $lead);

        $quotation = $this->quotation();

        if ($quotation) {
            $this->hydrateFromQuotation($quotation);
        } else {
            $this->resetDraft();
        }
    }

    #[On('sales-workspace-updated')]
    public function refreshWorkspace(): void
    {
    }

    public function render(): View
    {
        $lead = $this->lead();
        Gate::authorize('view', $lead);

        $quotation = $this->quotation();
        $meeting = $this->qualifyingMeeting();

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('service_name')
            ->get([
                'id',
                'service_name',
                'description',
                'standard_price',
            ])
            ->map(fn (Service $service): array => [
                'id' => (int) $service->getKey(),
                'name' => $service->service_name,
                'description' =>
                    (string) ($service->description ?? ''),
                'price' =>
                    (float) $service->standard_price,
            ])
            ->values()
            ->all();

        return view(
            'livewire.sales-workspace-quotation',
            [
                'lead' => $lead,
                'quotation' => $quotation,
                'qualifyingMeeting' => $meeting,
                'serviceOptions' => $services,
                'canCreate' =>
                    Gate::allows('create', Quotation::class),
                'canUpdate' =>
                    $quotation
                        ? Gate::allows('update', $quotation)
                        : false,
                'canApprove' =>
                    $quotation
                        ? Gate::allows('approve', $quotation)
                        : false,
                'canSend' =>
                    $quotation
                        ? Gate::allows('send', $quotation)
                        : false,
                'canDownload' =>
                    $quotation
                        ? Gate::allows('download', $quotation)
                        : false,
            ],
        );
    }

    public function openEditor(): void
    {
        $lead = $this->lead();
        $quotation = $this->quotation();

        if ($quotation) {
            Gate::authorize('update', $quotation);

            if ($quotation->status !== 'Draft') {
                Notification::make()
                    ->danger()
                    ->title(
                        'Only draft quotations can be edited.',
                    )
                    ->send();

                return;
            }

            $this->hydrateFromQuotation($quotation);
        } else {
            Gate::authorize('create', Quotation::class);

            if (! $this->qualifyingMeeting()) {
                Notification::make()
                    ->danger()
                    ->title(
                        'Complete a quotation-required meeting first.',
                    )
                    ->send();

                return;
            }

            $this->resetDraft();
        }

        $this->editorOpen = true;
        $this->resetValidation();
    }

    public function closeEditor(): void
    {
        $this->editorOpen = false;
        $this->resetValidation();
    }

    public function addItem(): void
    {
        $this->items[] = $this->blankItem();
        $this->recalculate();
    }

    public function removeItem(int $index): void
    {
        if (! array_key_exists($index, $this->items)) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->items[] = $this->blankItem();
        }

        $this->recalculate();
    }

    public function selectService(int $index): void
    {
        if (! array_key_exists($index, $this->items)) {
            return;
        }

        $serviceId = (int) (
            $this->items[$index]['service_id'] ?? 0
        );

        $service = Service::query()
            ->where('is_active', true)
            ->find($serviceId);

        if (! $service) {
            return;
        }

        $this->items[$index]['description'] =
            (string) ($service->description ?? '');

        $this->items[$index]['unit_price'] =
            (string) ((float) $service->standard_price);

        $this->recalculate();
    }

    public function updated(
        string $property,
        mixed $value = null,
    ): void {
        if (
            str_starts_with($property, 'items.')
            || in_array(
                $property,
                [
                    'discountType',
                    'discountValue',
                    'taxApplicable',
                    'taxPercentage',
                ],
                true,
            )
        ) {
            $this->recalculate();
        }
    }

    public function saveDraft(
        SalesWorkspaceQuotationService $service,
    ): void {
        $lead = $this->lead();
        $quotation = $this->quotation();

        if ($quotation) {
            Gate::authorize('update', $quotation);
        } else {
            Gate::authorize('create', Quotation::class);
        }

        $meeting = $quotation?->meeting
            ?: $this->qualifyingMeeting();

        abort_unless($meeting instanceof Meeting, 422);

        $quotation = $service->saveDraft(
            $lead,
            $meeting,
            $quotation,
            [
                'quotation_date' => $this->quotationDate,
                'valid_until' =>
                    filled($this->validUntil)
                        ? $this->validUntil
                        : null,
                'discount_type' => $this->discountType,
                'discount_value' => $this->discountValue,
                'tax_applicable' => $this->taxApplicable,
                'tax_percentage' => $this->taxPercentage,
                'customer_notes' =>
                    filled($this->customerNotes)
                        ? $this->customerNotes
                        : null,
                'internal_notes' =>
                    filled($this->internalNotes)
                        ? $this->internalNotes
                        : null,
                'items' => $this->items,
            ],
        );

        $this->quotationId = (int) $quotation->getKey();
        $this->hydrateFromQuotation($quotation);
        $this->editorOpen = true;

        Notification::make()
            ->success()
            ->title('Quotation draft saved.')
            ->send();

        $this->dispatch('sales-workspace-updated');
    }

    public function approveQuotation(
        SalesWorkspaceQuotationService $service,
    ): void {
        $lead = $this->lead();
        $quotation = $this->quotationOrFail();

        Gate::authorize('approve', $quotation);

        $quotation = $service->approve(
            $lead,
            $quotation,
        );

        $this->hydrateFromQuotation($quotation);
        $this->editorOpen = false;

        Notification::make()
            ->success()
            ->title('Quotation approved.')
            ->send();

        $this->dispatch('sales-workspace-updated');
    }

    public function sendQuotation(
        SalesWorkspaceQuotationService $service,
    ): void {
        $lead = $this->lead();
        $quotation = $this->quotationOrFail();

        Gate::authorize('send', $quotation);

        $sent = $service->send(
            $lead,
            $quotation,
        );

        $notification = Notification::make()
            ->title(
                $sent
                    ? 'Quotation emailed successfully.'
                    : 'Unable to send quotation email.',
            );

        $sent
            ? $notification->success()
            : $notification->danger();

        $notification->send();

        $quotation = $quotation->fresh([
            'items.service',
            'meeting',
        ]);

        if ($quotation) {
            $this->hydrateFromQuotation($quotation);
        }

        $this->dispatch('sales-workspace-updated');
    }

    public function downloadQuotation(
        QuotationPdfService $pdf,
    ): mixed {
        $quotation = $this->quotationOrFail();

        Gate::authorize('download', $quotation);

        return response()->streamDownload(
            fn () => print(
                $pdf->generate($quotation)->output()
            ),
            $quotation->quotation_code . '.pdf',
        );
    }

    private function lead(): Lead
    {
        return $this->scopedLeadQuery()
            ->with([
                'convertedCustomer',
                'assignedEmployee',
            ])
            ->findOrFail($this->leadId);
    }

    private function quotation(): ?Quotation
    {
        return Quotation::query()
            ->with([
                'items.service',
                'meeting',
                'customer',
                'invoice',
            ])
            ->where('lead_id', $this->leadId)
            ->latest('id')
            ->first();
    }

    private function quotationOrFail(): Quotation
    {
        $quotation = $this->quotation();

        abort_unless(
            $quotation instanceof Quotation,
            404,
        );

        return $quotation;
    }

    private function qualifyingMeeting(): ?Meeting
    {
        return Meeting::query()
            ->where('lead_id', $this->leadId)
            ->where('status', 'Completed')
            ->whereIn(
                'outcome',
                ['Quotation Required', 'Converted'],
            )
            ->latest('id')
            ->first();
    }

    private function scopedLeadQuery(): Builder
    {
        $query = Lead::query();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (
            method_exists($user, 'hasAnyRole')
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
            ->where('user_id', $user->getKey())
            ->value('id');

        if (! $employeeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'assigned_employee_id',
            $employeeId,
        );
    }

    private function resetDraft(): void
    {
        $this->quotationId = null;
        $this->quotationDate = now()->toDateString();
        $this->validUntil = now()
            ->addDays(15)
            ->toDateString();
        $this->discountType = 'fixed';
        $this->discountValue = '0';
        $this->taxApplicable = false;
        $this->taxPercentage = '18';
        $this->customerNotes = '';
        $this->internalNotes = '';
        $this->items = [$this->blankItem()];

        $this->recalculate();
    }

    private function hydrateFromQuotation(
        Quotation $quotation,
    ): void {
        $quotation->loadMissing('items.service');

        $this->quotationId =
            (int) $quotation->getKey();

        $this->quotationDate =
            $quotation->quotation_date
                ?->toDateString()
            ?? now()->toDateString();

        $this->validUntil =
            $quotation->valid_until
                ?->toDateString()
            ?? '';

        $this->discountType =
            (string) $quotation->discount_type;

        $this->discountValue =
            (string) $quotation->discount_value;

        $this->taxApplicable =
            (bool) $quotation->tax_applicable;

        $this->taxPercentage =
            (string) $quotation->tax_percentage;

        $this->customerNotes =
            (string) ($quotation->customer_notes ?? '');

        $this->internalNotes =
            (string) ($quotation->internal_notes ?? '');

        $this->items = $quotation->items
            ->sortBy('sort_order')
            ->map(fn ($item): array => [
                'service_id' => $item->service_id,
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'discount' => (string) $item->discount,
                'line_total' => (float) $item->line_total,
            ])
            ->values()
            ->all();

        if ($this->items === []) {
            $this->items[] = $this->blankItem();
        }

        $this->recalculate();
    }

    private function recalculate(): void
    {
        foreach ($this->items as $index => $item) {
            $quantity = max(
                (float) ($item['quantity'] ?? 0),
                0,
            );

            $unitPrice = max(
                (float) ($item['unit_price'] ?? 0),
                0,
            );

            $discount = max(
                (float) ($item['discount'] ?? 0),
                0,
            );

            $this->items[$index]['line_total'] = round(
                max(
                    ($quantity * $unitPrice) - $discount,
                    0,
                ),
                2,
            );
        }

        $this->totals = QuotationCalculator::calculate(
            items: $this->items,
            discountType: $this->discountType,
            discountValue:
                (float) $this->discountValue,
            taxApplicable: $this->taxApplicable,
            taxPercentage:
                (float) $this->taxPercentage,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function blankItem(): array
    {
        return [
            'service_id' => null,
            'description' => '',
            'quantity' => '1',
            'unit_price' => '0',
            'discount' => '0',
            'line_total' => 0,
        ];
    }
}