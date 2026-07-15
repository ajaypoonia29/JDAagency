<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Quotation;
use App\Models\Service;
use App\Support\QuotationCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class SalesWorkspaceQuotationService
{
    public function __construct(
        private readonly QuotationWorkflowService $workflow,
    ) {
    }

    public function saveDraft(
        Lead $lead,
        Meeting $meeting,
        ?Quotation $quotation,
        array $data,
    ): Quotation {
        return DB::transaction(function () use (
            $lead,
            $meeting,
            $quotation,
            $data,
        ): Quotation {
            $this->assertMeetingContext($lead, $meeting);

            [$payload, $items] = $this->prepare($data);

            if ($quotation) {
                $quotation = Quotation::query()
                    ->with(['invoice', 'items'])
                    ->lockForUpdate()
                    ->findOrFail($quotation->getKey());

                $this->assertQuotationContext(
                    $lead,
                    $meeting,
                    $quotation,
                );

                if ($quotation->status !== 'Draft') {
                    throw ValidationException::withMessages([
                        'quotation' =>
                            'Only draft quotations can be edited.',
                    ]);
                }

                if ($quotation->invoice) {
                    throw ValidationException::withMessages([
                        'quotation' =>
                            'Quotation items cannot change after an invoice has been created.',
                    ]);
                }

                $quotation = $this->workflow->update(
                    $quotation,
                    array_merge($payload, [
                        'lead_id' => $lead->getKey(),
                        'customer_id' =>
                            $lead->converted_customer_id,
                        'meeting_id' => $meeting->getKey(),
                    ]),
                );

                $quotation->items()->delete();
            } else {
                if (
                    Quotation::query()
                        ->where('lead_id', $lead->getKey())
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'quotation' =>
                            'This lead already has a quotation in its sales journey.',
                    ]);
                }

                $quotation = $this->workflow->create(
                    array_merge($payload, [
                        'lead_id' => $lead->getKey(),
                        'customer_id' =>
                            $lead->converted_customer_id,
                        'meeting_id' => $meeting->getKey(),
                        'assigned_employee_id' =>
                            $lead->assigned_employee_id,
                    ]),
                );
            }

            foreach ($items as $index => $item) {
                $quotation->items()->create([
                    'service_id' => $item['service_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'line_total' => $item['line_total'],
                    'sort_order' => $index + 1,
                ]);
            }

            return $quotation
                ->refresh()
                ->load([
                    'lead',
                    'customer',
                    'meeting',
                    'items.service',
                    'invoice',
                ]);
        }, attempts: 3);
    }

    public function approve(
        Lead $lead,
        Quotation $quotation,
    ): Quotation {
        $this->assertQuotationLead($lead, $quotation);

        return $this->workflow->approve($quotation);
    }

    public function send(
        Lead $lead,
        Quotation $quotation,
    ): bool {
        $this->assertQuotationLead($lead, $quotation);

        return $this->workflow->send($quotation);
    }

    /**
     * @return array{
     *     0: array<string, mixed>,
     *     1: array<int, array{
     *         service_id: int,
     *         description: string,
     *         quantity: float,
     *         unit_price: float,
     *         discount: float,
     *         line_total: float
     *     }>
     * }
     */
    private function prepare(array $data): array
    {
        $validated = Validator::make($data, [
            'quotation_date' => ['required', 'date'],
            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:quotation_date',
            ],
            'discount_type' => [
                'required',
                Rule::in(['fixed', 'percentage']),
            ],
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'tax_applicable' => [
                'required',
                'boolean',
            ],
            'tax_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'customer_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'internal_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],
            'items.*.service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],
            'items.*.description' => [
                'required',
                'string',
                'max:1000',
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'items.*.discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ])->validate();

        if (
            $validated['discount_type'] === 'percentage'
            && (float) $validated['discount_value'] > 100
        ) {
            throw ValidationException::withMessages([
                'discount_value' =>
                    'Percentage discount cannot exceed 100%.',
            ]);
        }

        $serviceIds = collect($validated['items'])
            ->pluck('service_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $activeServices = Service::query()
            ->whereIn('id', $serviceIds)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id);

        if ($activeServices->count() !== $serviceIds->count()) {
            throw ValidationException::withMessages([
                'items' =>
                    'Every quotation item must use an active service.',
            ]);
        }

        $items = [];

        foreach ($validated['items'] as $index => $item) {
            $quantity = round((float) $item['quantity'], 2);
            $unitPrice = round((float) $item['unit_price'], 2);
            $discount = round(
                (float) ($item['discount'] ?? 0),
                2,
            );

            $gross = round($quantity * $unitPrice, 2);

            if ($discount > $gross) {
                throw ValidationException::withMessages([
                    "items.$index.discount" =>
                        'Line discount cannot exceed the line gross amount.',
                ]);
            }

            $lineTotal = round($gross - $discount, 2);

            if ($lineTotal <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.line_total" =>
                        'Every quotation line must have a positive total.',
                ]);
            }

            $items[] = [
                'service_id' => (int) $item['service_id'],
                'description' => trim(
                    (string) $item['description'],
                ),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
            ];
        }

        $financials = QuotationCalculator::calculate(
            items: $items,
            discountType:
                (string) $validated['discount_type'],
            discountValue:
                (float) $validated['discount_value'],
            taxApplicable:
                (bool) $validated['tax_applicable'],
            taxPercentage:
                (float) $validated['tax_percentage'],
        );

        if ($financials['grand_total'] <= 0) {
            throw ValidationException::withMessages([
                'grand_total' =>
                    'Quotation grand total must be positive.',
            ]);
        }

        return [
            [
                'quotation_date' =>
                    $validated['quotation_date'],
                'valid_until' =>
                    $validated['valid_until'] ?? null,
                'subtotal' => $financials['subtotal'],
                'discount_type' =>
                    $validated['discount_type'],
                'discount_value' =>
                    round(
                        (float) $validated['discount_value'],
                        2,
                    ),
                'tax_applicable' =>
                    (bool) $validated['tax_applicable'],
                'tax_percentage' =>
                    round(
                        (float) $validated['tax_percentage'],
                        2,
                    ),
                'tax' => $financials['tax'],
                'grand_total' =>
                    $financials['grand_total'],
                'customer_notes' =>
                    $validated['customer_notes'] ?? null,
                'internal_notes' =>
                    $validated['internal_notes'] ?? null,
                'is_active' => true,
            ],
            $items,
        ];
    }

    private function assertMeetingContext(
        Lead $lead,
        Meeting $meeting,
    ): void {
        if (
            (int) $meeting->lead_id !== (int) $lead->getKey()
            || $meeting->status !== 'Completed'
            || ! in_array(
                $meeting->outcome,
                ['Quotation Required', 'Converted'],
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'meeting_id' =>
                    'Use a completed quotation-required meeting belonging to this lead.',
            ]);
        }

        if ($lead->lead_status === 'Lost') {
            throw ValidationException::withMessages([
                'lead_id' =>
                    'A quotation cannot be created for a lost lead.',
            ]);
        }
    }

    private function assertQuotationContext(
        Lead $lead,
        Meeting $meeting,
        Quotation $quotation,
    ): void {
        $this->assertQuotationLead($lead, $quotation);

        if (
            (int) $quotation->meeting_id
                !== (int) $meeting->getKey()
            || (int) $quotation->customer_id
                !== (int) $lead->converted_customer_id
        ) {
            throw ValidationException::withMessages([
                'quotation' =>
                    'The quotation does not belong to this sales journey.',
            ]);
        }
    }

    private function assertQuotationLead(
        Lead $lead,
        Quotation $quotation,
    ): void {
        if (
            (int) $quotation->lead_id
                !== (int) $lead->getKey()
        ) {
            throw ValidationException::withMessages([
                'quotation' =>
                    'The quotation does not belong to this lead.',
            ]);
        }
    }
}