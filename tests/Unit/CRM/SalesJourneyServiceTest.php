<?php

declare(strict_types=1);

namespace Tests\Unit\CRM;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Payment;
use App\Models\Quotation;
use App\Services\CRM\SalesJourneyService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class SalesJourneyServiceTest extends TestCase
{
    public function test_new_lead_recommends_contact(): void
    {
        $lead = $this->lead('New');

        $action = app(SalesJourneyService::class)
            ->nextAction($lead);

        $this->assertSame('mark-contacted', $action['key']);
    }

    public function test_scheduled_meeting_recommends_completion(): void
    {
        $lead = $this->lead('Meeting Scheduled');

        $lead->setRelation(
            'meetings',
            new EloquentCollection([
                new Meeting([
                    'status' => 'Scheduled',
                    'outcome' => 'Pending',
                ]),
            ]),
        );

        $action = app(SalesJourneyService::class)
            ->nextAction($lead);

        $this->assertSame('complete-meeting', $action['key']);
    }

    public function test_completed_meeting_recommends_quotation(): void
    {
        $lead = $this->lead('Meeting Scheduled');

        $lead->setRelation(
            'meetings',
            new EloquentCollection([
                new Meeting([
                    'status' => 'Completed',
                    'outcome' => 'Quotation Required',
                ]),
            ]),
        );

        $action = app(SalesJourneyService::class)
            ->nextAction($lead);

        $this->assertSame('create-quotation', $action['key']);
    }

    public function test_invoice_balance_recommends_payment(): void
    {
        $lead = $this->lead('Proposal Sent');

        $quotation = new Quotation([
            'status' => 'Sent',
            'payment_status' => 'Partially Paid',
            'grand_total' => 53100,
        ]);
        $quotation->setRelation(
            'payments',
            new EloquentCollection([
                new Payment([
                    'amount' => 25000,
                ]),
            ]),
        );

        $lead->setRelation(
            'quotations',
            new EloquentCollection([$quotation]),
        );
        $lead->setRelation(
            'invoices',
            new EloquentCollection([
                new Invoice([
                    'status' => 'Partially Paid',
                    'balance_due' => 28100,
                ]),
            ]),
        );

        $action = app(SalesJourneyService::class)
            ->nextAction($lead);

        $this->assertSame('receive-payment', $action['key']);
        $this->assertStringContainsString(
            '28,100.00',
            $action['description'],
        );
    }

    public function test_won_lead_is_terminal(): void
    {
        $lead = $this->lead('Won');

        $action = app(SalesJourneyService::class)
            ->nextAction($lead);

        $this->assertSame('completed', $action['key']);
    }

    private function lead(string $status): Lead
    {
        $lead = new Lead([
            'lead_status' => $status,
            'company_name' => 'Poonia Growth Studio',
            'contact_person' => 'Ajay Poonia',
        ]);

        $lead->setRelation(
            'meetings',
            new EloquentCollection(),
        );
        $lead->setRelation(
            'quotations',
            new EloquentCollection(),
        );
        $lead->setRelation(
            'invoices',
            new EloquentCollection(),
        );

        return $lead;
    }
}
