<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Meeting;
use App\Services\CRM\LeadStatusService;
use App\Services\CRM\LeadWorkflowService;
use App\Services\CRM\MeetingWorkflowService;
use App\Services\CRM\SalesJourneyService;
use App\Support\CRM\LeadOptionCatalog;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use UnitEnum;

class SalesWorkspace extends Page
{
    private const ASSIGNMENT_MANAGER_ROLES = [
        'Admin',
        'Developer',
        'Sales Manager',
        'Manager',
        'General Manager',
        'Director',
        'Chief Executive Officer',
    ];

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

    public bool $showLeadForm = false;

    public string $leadCompanyName = '';

    public string $leadContactPerson = '';

    public string $leadDesignation = '';

    public string $leadEmail = '';

    public string $leadPhone = '';

    public string $leadWhatsapp = '';

    public string $leadSource = '';

    public string $leadPriority = 'Medium';

    public string $leadIndustry = '';

    public string $leadBusinessType = '';

    public string $leadCompanySize = '';

    public string $leadEstimatedValue = '0';

    public ?int $leadAssignedEmployeeId = null;

    public string $leadRequirementsSummary = '';

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

        $canManageLeadAssignments =
            $this->canManageLeadAssignments();

        $leadAssignmentEmployee =
            $this->currentActiveEmployee();

        $leadDisplayStatuses = $leads
            ->mapWithKeys(
                fn (Lead $pipelineLead): array => [
                    (int) $pipelineLead->getKey() =>
                        $journey->displayLeadStatus(
                            $pipelineLead,
                        ),
                ],
            )
            ->all();

        return [
            'leads' => $leads,
            'selectedLead' => $lead,
            'statusOptions' => self::STATUS_OPTIONS,
            'canCreateLead' => Gate::allows(
                'create',
                Lead::class,
            ),
            'canManageLeadAssignments' =>
                $canManageLeadAssignments,
            'leadAssignmentEmployee' =>
                $leadAssignmentEmployee,
            'leadEmployees' => $canManageLeadAssignments
                ? Employee::query()
                    ->where('is_active', true)
                    ->orderBy('full_name')
                    ->get(['id', 'full_name'])
                : collect(),
            'leadDesignationOptions' =>
                LeadOptionCatalog::designations(),
            'leadIndustryOptions' =>
                LeadOptionCatalog::industries(),
            'leadBusinessTypeOptions' =>
                LeadOptionCatalog::businessTypes(),
            'leadCompanySizeOptions' =>
                LeadOptionCatalog::companySizes(),
            'leadSourceOptions' =>
                LeadOptionCatalog::leadSources(),
            'leadEstimatedValueOptions' =>
                LeadOptionCatalog::estimatedValues(),
            'leadDisplayStatuses' => $leadDisplayStatuses,
            'selectedLeadDisplayStatus' => $lead
                ? $journey->displayLeadStatus($lead)
                : null,
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
        $this->showLeadForm = false;
        $this->resetValidation();
    }

    public function openLeadForm(): void
    {
        Gate::authorize('create', Lead::class);

        $this->resetLeadFormFields();

        $employee = $this->currentActiveEmployee();

        $this->leadAssignedEmployeeId = $employee
            ? (int) $employee->getKey()
            : null;

        $this->showMeetingForm = false;
        $this->showLeadForm = true;
        $this->resetValidation();

        if (
            ! $this->canManageLeadAssignments()
            && ! $employee
        ) {
            $this->addError(
                'leadAssignedEmployeeId',
                'Your account must be linked to an active employee before creating leads.',
            );
        }
    }

    public function closeLeadForm(): void
    {
        $this->showLeadForm = false;
        $this->resetLeadFormFields();
        $this->resetValidation();
    }

    public function createLead(): void
    {
        Gate::authorize('create', Lead::class);

        $canManageLeadAssignments =
            $this->canManageLeadAssignments();

        $currentEmployee =
            $this->currentActiveEmployee();

        if (
            ! $canManageLeadAssignments
            && ! $currentEmployee
        ) {
            throw ValidationException::withMessages([
                'leadAssignedEmployeeId' => [
                    'Your account must be linked to an active employee before creating leads.',
                ],
            ]);
        }

        $assignmentRules = $canManageLeadAssignments
            ? [
                'nullable',
                'integer',
                Rule::exists(
                    'employees',
                    'id',
                )->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at'),
                ),
            ]
            : [
                'required',
                'integer',
                Rule::in([
                    (int) $currentEmployee->getKey(),
                ]),
            ];

        $validated = $this->validate([
            'leadCompanyName' => [
                'nullable',
                'string',
                'max:255',
            ],
            'leadContactPerson' => [
                'required',
                'string',
                'max:255',
            ],
            'leadDesignation' => [
                'nullable',
                'string',
                'max:255',
            ],
            'leadEmail' => [
                'required',
                'email',
                'max:255',
            ],
            'leadPhone' => [
                'required',
                'string',
                'max:50',
            ],
            'leadWhatsapp' => [
                'nullable',
                'string',
                'max:50',
            ],
            'leadSource' => [
                'nullable',
                'string',
                'max:255',
            ],
            'leadPriority' => [
                'required',
                Rule::in([
                    'Low',
                    'Medium',
                    'High',
                    'Urgent',
                ]),
            ],
            'leadIndustry' => [
                'nullable',
                'string',
                'max:255',
            ],
            'leadBusinessType' => [
                'nullable',
                'string',
                'max:255',
            ],
            'leadCompanySize' => [
                'nullable',
                'string',
                'max:255',
            ],
            'leadEstimatedValue' => [
                'required',
                'numeric',
                'min:0',
            ],
            'leadAssignedEmployeeId' =>
                $assignmentRules,
            'leadRequirementsSummary' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        try {
            $lead = app(LeadWorkflowService::class)
                ->create([
                    'lead_status' => 'New',
                    'priority' =>
                        $validated['leadPriority'],
                    'company_name' => filled(
                        $validated['leadCompanyName'],
                    )
                        ? trim(
                            $validated['leadCompanyName'],
                        )
                        : null,
                    'contact_person' => trim(
                        $validated['leadContactPerson'],
                    ),
                    'designation' => filled(
                        $validated['leadDesignation'],
                    )
                        ? trim(
                            $validated['leadDesignation'],
                        )
                        : null,
                    'email' => trim(
                        $validated['leadEmail'],
                    ),
                    'phone' => trim(
                        $validated['leadPhone'],
                    ),
                    'whatsapp' => filled(
                        $validated['leadWhatsapp'],
                    )
                        ? trim(
                            $validated['leadWhatsapp'],
                        )
                        : null,
                    'lead_source' => filled(
                        $validated['leadSource'],
                    )
                        ? trim(
                            $validated['leadSource'],
                        )
                        : null,
                    'industry' => filled(
                        $validated['leadIndustry'],
                    )
                        ? trim(
                            $validated['leadIndustry'],
                        )
                        : null,
                    'business_type' => filled(
                        $validated['leadBusinessType'],
                    )
                        ? trim(
                            $validated['leadBusinessType'],
                        )
                        : null,
                    'company_size' => filled(
                        $validated['leadCompanySize'],
                    )
                        ? trim(
                            $validated['leadCompanySize'],
                        )
                        : null,
                    'estimated_value' => (float)
                        $validated['leadEstimatedValue'],
                    'assigned_employee_id' =>
                        $canManageLeadAssignments
                            ? $validated[
                                'leadAssignedEmployeeId'
                            ]
                            : (int)
                                $currentEmployee->getKey(),
                    'requirements_summary' => filled(
                        $validated[
                            'leadRequirementsSummary'
                        ],
                    )
                        ? trim(
                            $validated[
                                'leadRequirementsSummary'
                            ],
                        )
                        : null,
                    'is_active' => true,
                ]);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                $this->mapLeadValidationErrors(
                    $exception->errors(),
                ),
            );
        }

        $this->search = '';
        $this->status = 'all';
        $this->selectedLeadId =
            (int) $lead->getKey();
        $this->showLeadForm = false;

        $this->resetLeadFormFields();
        $this->resetValidation();

        $this->dispatch(
            'sales-workspace-updated',
        );

        Notification::make()
            ->success()
            ->title(
                sprintf(
                    'Lead %s created successfully.',
                    $lead->lead_code,
                ),
            )
            ->body(
                sprintf(
                    'Customer %s is linked and the lead is selected.',
                    $lead->convertedCustomer?->customer_code
                        ?: 'record',
                ),
            )
            ->send();
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


    #[On('sales-workspace-updated')]
    public function refreshWorkspace(): void
    {
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

        if (! auth()->user()) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->canManageLeadAssignments()) {
            return $query;
        }

        $employee = $this->currentActiveEmployee();

        if (! $employee) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'assigned_employee_id',
            $employee->getKey(),
        );
    }

    private function canManageLeadAssignments(): bool
    {
        $user = auth()->user();

        return (bool) (
            $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(
                self::ASSIGNMENT_MANAGER_ROLES,
            )
        );
    }

    private function currentActiveEmployee(): ?Employee
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return Employee::query()
            ->where('user_id', $user->getKey())
            ->where('is_active', true)
            ->orderBy('id')
            ->first([
                'id',
                'full_name',
            ]);
    }

    private function resetLeadFormFields(): void
    {
        $this->leadCompanyName = '';
        $this->leadContactPerson = '';
        $this->leadDesignation = '';
        $this->leadEmail = '';
        $this->leadPhone = '';
        $this->leadWhatsapp = '';
        $this->leadSource = '';
        $this->leadPriority = 'Medium';
        $this->leadIndustry = '';
        $this->leadBusinessType = '';
        $this->leadCompanySize = '';
        $this->leadEstimatedValue = '0';
        $this->leadAssignedEmployeeId = null;
        $this->leadRequirementsSummary = '';
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function mapLeadValidationErrors(
        array $errors,
    ): array {
        $fieldMap = [
            'company_name' => 'leadCompanyName',
            'contact_person' => 'leadContactPerson',
            'designation' => 'leadDesignation',
            'email' => 'leadEmail',
            'phone' => 'leadPhone',
            'whatsapp' => 'leadWhatsapp',
            'lead_source' => 'leadSource',
            'priority' => 'leadPriority',
            'industry' => 'leadIndustry',
            'business_type' => 'leadBusinessType',
            'company_size' => 'leadCompanySize',
            'estimated_value' =>
                'leadEstimatedValue',
            'assigned_employee_id' =>
                'leadAssignedEmployeeId',
            'requirements_summary' =>
                'leadRequirementsSummary',
        ];

        $mapped = [];

        foreach ($errors as $field => $messages) {
            $mapped[$fieldMap[$field] ?? $field] =
                $messages;
        }

        return $mapped;
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
