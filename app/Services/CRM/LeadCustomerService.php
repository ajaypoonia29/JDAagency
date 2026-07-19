<?php

declare(strict_types=1);

namespace App\Services\CRM;

use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadCustomerService
{
    public static function sync(Lead $lead): Customer
    {
        return app(self::class)->synchronize($lead);
    }

    public function synchronize(Lead $lead): Customer
    {
        return DB::transaction(function () use ($lead): Customer {
            $lockedLead = Lead::withTrashed()
                ->lockForUpdate()
                ->findOrFail($lead->getKey());

            return $this->synchronizeLocked($lockedLead);
        }, attempts: 3);
    }

    /**
     * The caller must already be inside a transaction.
     */
    public function synchronizeLocked(Lead $lead): Customer
    {
        $customer = $this->linkedCustomer($lead);

        if (! $customer) {
            $customer = $this->matchedCustomer($lead);
        }

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }

            if ($customer->customer_status === 'Blacklisted') {
                throw ValidationException::withMessages([
                    'converted_customer_id' =>
                        'This lead matches a blacklisted customer and cannot be linked automatically.',
                ]);
            }

            $this->fillMissingCustomerData($customer, $lead);
        } else {
            $customer = Customer::query()->create([
                'customer_code' => $this->nextCustomerCode(),
                ...$this->customerData($lead),
            ]);
        }

        if ((int) $lead->converted_customer_id !== (int) $customer->id) {
            $lead->updateQuietly([
                'converted_customer_id' => $customer->id,
            ]);
        }

        return $customer->refresh();
    }

    private function linkedCustomer(Lead $lead): ?Customer
    {
        if (! $lead->converted_customer_id) {
            return null;
        }

        return Customer::withTrashed()
            ->lockForUpdate()
            ->find($lead->converted_customer_id);
    }

    private function matchedCustomer(Lead $lead): ?Customer
    {
        $emailMatches = $this->emailMatches($lead->email);
        $phoneMatches = $this->phoneMatches($lead->phone);

        $this->assertSingleMatch($emailMatches, 'email');
        $this->assertSingleMatch($phoneMatches, 'phone');

        $emailCustomer = $emailMatches->first();
        $phoneCustomer = $phoneMatches->first();

        if (
            $emailCustomer
            && $phoneCustomer
            && $emailCustomer->getKey() !== $phoneCustomer->getKey()
        ) {
            throw ValidationException::withMessages([
                'email' =>
                    'The lead email and phone belong to different customers. Link the customer manually after resolving the duplicate data.',
                'phone' =>
                    'The lead email and phone belong to different customers. Link the customer manually after resolving the duplicate data.',
            ]);
        }

        $customer = $emailCustomer ?: $phoneCustomer;

        if (! $customer) {
            return null;
        }

        $matchedByEmail = $emailCustomer !== null;
        $matchedByPhone = $phoneCustomer !== null;

        if (
            $matchedByEmail
            && filled($customer->primary_phone)
            && $this->normalizePhone($customer->primary_phone)
                !== $this->normalizePhone($lead->phone)
        ) {
            throw ValidationException::withMessages([
                'phone' =>
                    'The matching customer email has a different phone number. Automatic linking was stopped to prevent a wrong customer match.',
            ]);
        }

        if (
            $matchedByPhone
            && filled($customer->primary_email)
            && $this->normalizeEmail($customer->primary_email)
                !== $this->normalizeEmail($lead->email)
        ) {
            throw ValidationException::withMessages([
                'email' =>
                    'The matching customer phone has a different email address. Automatic linking was stopped to prevent a wrong customer match.',
            ]);
        }

        return $customer;
    }

    /**
     * @return Collection<int, Customer>
     */
    private function emailMatches(?string $email): Collection
    {
        $email = $this->normalizeEmail($email);

        if (! $email) {
            return new Collection();
        }

        return Customer::withTrashed()
            ->whereRaw('LOWER(primary_email) = ?', [$email])
            ->lockForUpdate()
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    private function phoneMatches(?string $phone): Collection
    {
        $phone = $this->normalizePhone($phone);

        if (! $phone) {
            return new Collection();
        }

        return Customer::withTrashed()
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(primary_phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?",
                [$phone],
            )
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param Collection<int, Customer> $matches
     */
    private function assertSingleMatch(
        Collection $matches,
        string $field,
    ): void {
        if ($matches->count() <= 1) {
            return;
        }

        throw ValidationException::withMessages([
            $field =>
                "Multiple customers match this {$field}. Resolve the duplicates before converting the lead.",
        ]);
    }

    private function fillMissingCustomerData(
        Customer $customer,
        Lead $lead,
    ): void {
        $incoming = $this->customerData($lead);
        $safeFields = [
            'company_name',
            'display_name',
            'contact_person',
            'designation',
            'industry',
            'business_category',
            'primary_email',
            'primary_phone',
            'whatsapp',
            'website',
            'business_address',
            'billing_address',
            'latitude',
            'longitude',
            'google_maps_link',
            'assigned_employee_id',
            'lead_source',
            'notes',
        ];

        $changes = [];

        foreach ($safeFields as $field) {
            if (
                blank($customer->{$field})
                && filled($incoming[$field] ?? null)
            ) {
                $changes[$field] = $incoming[$field];
            }
        }

        $nextStatus = $this->progressedCustomerStatus(
            (string) $customer->customer_status,
            $lead,
        );

        if ($nextStatus !== $customer->customer_status) {
            $changes['customer_status'] = $nextStatus;
        }

        if ($changes !== []) {
            $customer->update($changes);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function customerData(Lead $lead): array
    {
        $displayName = filled($lead->company_name)
            ? $lead->company_name
            : $lead->contact_person;

        return [
            'customer_type' => filled($lead->company_name)
                ? 'Business'
                : 'Individual',
            'customer_status' => $this->newCustomerStatus($lead),
            'company_name' => $lead->company_name,
            'display_name' => $displayName,
            'contact_person' => $lead->contact_person,
            'designation' => $lead->designation,
            'industry' => $lead->industry,
            'business_category' => $lead->business_type,
            'primary_email' => $lead->email,
            'primary_phone' => $lead->phone,
            'whatsapp' => $lead->whatsapp,
            'website' => $lead->website,
            'business_address' => $lead->business_address,
            'billing_address' => $lead->business_address,
            'latitude' => $lead->latitude,
            'longitude' => $lead->longitude,
            'google_maps_link' => $lead->google_maps_link,
            'assigned_employee_id' => $lead->assigned_employee_id,
            'lead_source' => $lead->lead_source,
            'credit_limit' => 0,
            'currency' => 'INR',
            'notes' => $this->notes($lead),
            'is_active' => (bool) $lead->is_active,
        ];
    }

    private function newCustomerStatus(Lead $lead): string
    {
        return match ($lead->lead_status) {
            'Won' => 'Active',
            'Qualified',
            'Meeting Scheduled',
            'Proposal Sent',
            'Negotiation' => 'Prospect',
            default => 'Lead',
        };
    }

    private function progressedCustomerStatus(
        string $current,
        Lead $lead,
    ): string {
        if (in_array(
            $current,
            ['Active', 'Inactive', 'Blacklisted'],
            true,
        )) {
            return $current;
        }

        if ($lead->lead_status === 'Won') {
            return 'Active';
        }

        if (
            $current === 'Lead'
            && in_array(
                $lead->lead_status,
                [
                    'Qualified',
                    'Meeting Scheduled',
                    'Proposal Sent',
                    'Negotiation',
                ],
                true,
            )
        ) {
            return 'Prospect';
        }

        return $current;
    }

    private function notes(Lead $lead): ?string
    {
        $notes = collect([
            filled($lead->requirements_summary)
                ? 'Requirements: ' . $lead->requirements_summary
                : null,
            filled($lead->internal_notes)
                ? 'Internal Notes: ' . $lead->internal_notes
                : null,
        ])->filter()->implode(PHP_EOL . PHP_EOL);

        return filled($notes) ? $notes : null;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = mb_strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        return filled($phone) ? $phone : null;
    }

    private function nextCustomerCode(): string
    {
        $lastCustomer = Customer::withTrashed()
            ->orderByDesc('id')
            ->first();

        $next = 1;

        if ($lastCustomer && filled($lastCustomer->customer_code)) {
            $next = ((int) substr($lastCustomer->customer_code, -4)) + 1;
        }

        return 'CUS-' . str_pad(
            (string) $next,
            4,
            '0',
            STR_PAD_LEFT,
        );
    }
}
