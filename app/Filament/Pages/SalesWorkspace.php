<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Meeting;
use App\Services\CRM\LeadStatusService;
use App\Services\CRM\MeetingWorkflowService;
use App\Services\CRM\SalesJourneyService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use UnitEnum;

class SalesWorkspace extends Page
{
    private const STATUS_OPTIONS = [
        'all' => 'All active leads',
        'New' => 'New',
        'Contacted' => 'Contacted',
        'Qualified' => 'Qualified',
        'Meeting Scheduled' => 'Meeting Scheduled',
        'Proposal Sent' => 'Proposal Sent',
        'Negotiation' => 'Negotiation',
        'Won' => 'Won',
        'Lost' => 'Lost',
    ];

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Sales Workspace';

    protected static ?string $title = 'Unified Sales Journey';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.sales-workspace';

    public string $search = '';

    public string $status = 'all';

    public ?int $selectedLeadId = null;

    public bool $showMeetingForm = false;

    public string $meetingTitle = '';

    public string $meetingType = 'Online';

    public string $meetingDate = '';

    public string $meetingTime = '11:00';

    public int $meetingDuration = 45;

    public string $meetingNotes = '';

    public function mount(): void
    {
        $requestedLead = request()->integer('lead');

        if ($requestedLead > 0) {
            $this->selectedLeadId = $requestedLead;
        }

        $this->meetingDate = now()
            ->addDay()
            ->format('Y-m-d');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $leads = $this->filteredLeads();
        $lead = $this->selectedLead();

        $journey = app(SalesJourneyService::class);

        return [
            'leads' => $leads,
            'selectedLead' => $lead,
            'statusOptions' => self::STATUS_OPTIONS,
            'pipelineCounts' => $this->pipelineCounts(),
            'stages' => $lead ? $journey->stages($lead) : [],
            'nextAction' => $lead
                ? $journey->nextAction($lead)
                : null,
            'summary' => $lead ? $journey->summary($lead) : null,
            'timeline' => $lead
                ? $journey->timeline($lead)
                : [],
            'latestMeeting' => $lead
                ? $journey->latestMeeting($lead)
                : null,
            'latestQuotation' => $lead
                ? $journey->latestQuotation($lead)
                : null,
            'latestInvoice' => $lead
                ? $journey->latestInvoice($lead)
                : null,
            'payments' => $lead
                ? $journey->payments($lead)
                : collect(),
            'quotationCreateUrl' =>
                $this->quotationCreateUrl($lead, $journey),
        ];
    }

    public function selectLead(int $leadId): void
    {
        $lead = $this->scopedLeadQuery()->findOrFail($leadId);

        Gate::authorize('view', $lead);

        $this->selectedLeadId = (int) $lead->getKey();
        $this->showMeetingForm = false;
        $this->resetValidation();
    }

    public function transitionLead(string $target): void
    {
        $lead = $this->selectedLeadOrFail();

        Gate::authorize('update', $lead);

        app(LeadStatusService::class)->transition($lead, $target);

        $this->notifySuccess(
            sprintf('Lead moved to %s.', $target),
        );
    }

    public function openMeetingForm(): void
    {
        $lead = $this->selectedLeadOrFail();

        Gate::authorize('update', $lead);

        $this->meetingTitle = sprintf(
            'Discovery Meeting - %s',
            $lead->company_name ?: $lead->contact_person,
        );
        $this->meetingType = 'Online';
        $this->meetingDate = now()
            ->addDay()
            ->format('Y-m-d');
        $this->meetingTime = '11:00';
        $this->meetingDuration = 45;
        $this->meetingNotes = '';
        $this->showMeetingForm = true;
        $this->resetValidation();
    }

    public function closeMeetingForm(): void
    {
        $this->showMeetingForm = false;
        $this->resetValidation();
    }

    public function scheduleMeeting(): void
    {
        $lead = $this->selectedLeadOrFail();

        Gate::authorize('update', $lead);

        $validated = $this->validate([
            'meetingTitle' => ['required', 'string', 'max:255'],
            'meetingType' => [
                'required',
                Rule::in(['Office', 'Client Site', 'Online', 'Phone']),
            ],
            'meetingDate' => ['required', 'date'],
            'meetingTime' => ['required', 'date_format:H:i'],
            'meetingDuration' => ['required', 'integer', 'min:0', 'max:1440'],
            'meetingNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        app(MeetingWorkflowService::class)->create([
            'lead_id' => $lead->getKey(),
            'assigned_employee_id' =>
                $lead->assigned_employee_id,
            'meeting_title' => $validated['meetingTitle'],
            'meeting_type' => $validated['meetingType'],
            'meeting_date' => $validated['meetingDate'],
            'meeting_time' => $validated['meetingTime'],
            'expected_duration' =>
                $validated['meetingDuration'],
            'status' => 'Scheduled',
            'outcome' => 'Pending',
            'meeting_notes' => $validated['meetingNotes'],
            'is_active' => true,
        ]);

        $this->showMeetingForm = false;

        $this->notifySuccess(
            'Meeting scheduled and lead synchronized.',
        );
    }

    public function completeMeeting(
        int $meetingId,
        string $outcome,
    ): void {
        $lead = $this->selectedLeadOrFail();

        Gate::authorize('update', $lead);

        $meeting = $lead->meetings
            ->firstWhere('id', $meetingId);

        abort_unless($meeting instanceof Meeting, 404);

        app(MeetingWorkflowService::class)->update(
            $meeting,
            [
                'status' => 'Completed',
                'outcome' => $outcome,
            ],
        );

        $this->notifySuccess(
            sprintf('Meeting completed as %s.', $outcome),
        );
    }

    public function updatedSearch(): void
    {
        $this->selectedLeadId = null;
    }

    public function updatedStatus(): void
    {
        $this->selectedLeadId = null;
    }

    /**
     * @return Collection<int, Lead>
     */
    private function filteredLeads(): Collection
    {
        return $this->scopedLeadQuery()
            ->with([
                'assignedEmployee',
                'convertedCustomer',
                'meetings',
                'quotations.payments',
                'quotations.invoice',
                'invoices',
            ])
            ->when(
                $this->status !== 'all',
                fn (Builder $query): Builder =>
                    $query->where('lead_status', $this->status),
            )
            ->when(
                filled($this->search),
                function (Builder $query): Builder {
                    $term = '%' . trim($this->search) . '%';

                    return $query->where(
                        function (Builder $search) use ($term): void {
                            $search
                                ->where('lead_code', 'like', $term)
                                ->orWhere('company_name', 'like', $term)
                                ->orWhere('contact_person', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        },
                    );
                },
            )
            ->orderByRaw(
                "CASE lead_status
                    WHEN 'New' THEN 10
                    WHEN 'Contacted' THEN 20
                    WHEN 'Qualified' THEN 30
                    WHEN 'Meeting Scheduled' THEN 40
                    WHEN 'Proposal Sent' THEN 50
                    WHEN 'Negotiation' THEN 60
                    WHEN 'Won' THEN 70
                    WHEN 'Lost' THEN 80
                    ELSE 90
                END"
            )
            ->orderBy('next_follow_up_date')
            ->latest('id')
            ->limit(100)
            ->get();
    }

    private function selectedLead(): ?Lead
    {
        $query = $this->scopedLeadQuery()->with([
            'assignedEmployee',
            'convertedCustomer',
            'meetings',
            'quotations.payments',
            'quotations.invoice',
            'invoices',
        ]);

        if ($this->selectedLeadId) {
            return $query->find($this->selectedLeadId);
        }

        return $query
            ->latest('id')
            ->first();
    }

    private function selectedLeadOrFail(): Lead
    {
        $lead = $this->selectedLead();

        abort_unless($lead instanceof Lead, 404);

        return $lead;
    }

    /**
     * @return array<string, int>
     */
    private function pipelineCounts(): array
    {
        $counts = $this->scopedLeadQuery()
            ->selectRaw('lead_status, COUNT(*) AS aggregate')
            ->groupBy('lead_status')
            ->pluck('aggregate', 'lead_status')
            ->map(
                fn (mixed $count): int => (int) $count,
            );

        return collect(self::STATUS_OPTIONS)
            ->mapWithKeys(
                fn (string $label, string $status): array => [
                    $status => $status === 'all'
                        ? (int) $counts->sum()
                        : (int) ($counts[$status] ?? 0),
                ],
            )
            ->all();
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

    private function quotationCreateUrl(
        ?Lead $lead,
        SalesJourneyService $journey,
    ): ?string {
        if (! $lead || ! QuotationResource::canCreate()) {
            return null;
        }

        $meeting = $journey->latestMeeting($lead);

        if (
            ! $meeting
            || $meeting->status !== 'Completed'
            || $lead->quotations->isNotEmpty()
        ) {
            return null;
        }

        return QuotationResource::getUrl('create', [
            'meeting_id' => $meeting->getKey(),
            'lead_id' => $lead->getKey(),
        ]);
    }

    private function notifySuccess(string $message): void
    {
        Notification::make()
            ->success()
            ->title($message)
            ->send();
    }
}
