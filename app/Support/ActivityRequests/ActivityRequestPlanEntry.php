<?php

namespace App\Support\ActivityRequests;

use App\Enums\ActivityRequestSkipReason;
use App\Enums\InstitutionActivityCampaign;
use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionActivityStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * One institution's row of a send plan: who would be asked, about which period, and who is left
 * out and why. The preview endpoint serialises it; the send executes it.
 *
 * @phpstan-type RecipientRow array{id: string, name: string, period_start: string, period_end: string, delivery_mode: string, skip_reason: string|null, incomplete_meetings: list<array{id: string, date: string, status: string}>}
 */
final readonly class ActivityRequestPlanEntry
{
    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<string, RecipientRow>  $recipientPeriods  keyed by recipient id
     * @param  list<RecipientRow>  $excludedRecipients
     */
    public function __construct(
        public Institution $institution,
        public InstitutionActivityCampaign $campaign,
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public Collection $recipients = new Collection,
        public array $recipientPeriods = [],
        public array $excludedRecipients = [],
        public ?ActivityRequestSkipReason $skipReason = null,
    ) {}

    /** @return array<string, mixed> */
    public function toPreviewArray(): array
    {
        return [
            'institution' => ['id' => (string) $this->institution->id, 'name' => is_string($this->institution->name) ? $this->institution->name : ''],
            'activity_status' => $this->skipReason === ActivityRequestSkipReason::NotAllowed
                ? null
                : app(InstitutionActivityStatusService::class)->resolve($this->institution)->toArray(),
            'recipients' => array_values($this->recipientPeriods),
            'excluded_recipients' => $this->excludedRecipients,
            'recipient_count' => $this->recipients->count(),
            'campaign_type' => $this->campaign->value,
            'period_end' => $this->periodEnd->toDateString(),
            'period_start' => $this->periodStart->toDateString(),
            'skip_reason' => $this->skipReason?->value,
        ];
    }
}
