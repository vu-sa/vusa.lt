<?php

namespace App\Models;

use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Services\MeetingCompletionService;
use Database\Factories\InstitutionActivityRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * The signed link authorizes only this recipient's answer.
 *
 * @property string $id
 * @property string $send_id
 * @property string $institution_id
 * @property string $recipient_id
 * @property string|null $requested_by_id
 * @property string|null $task_id
 * @property Carbon $period_start
 * @property string|null $note
 * @property InstitutionActivityAnswer|null $answer
 * @property Carbon|null $answered_at
 * @property Carbon|null $resolved_at
 * @property string|null $meeting_id
 * @property string|null $check_in_id
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property InstitutionActivityCampaign $campaign_type
 * @property Carbon|null $period_end
 * @property string|null $locale
 * @property string|null $resolution_source
 * @property string|null $resolved_by_request_id
 * @property-read InstitutionCheckIn|null $checkIn
 * @property-read Collection<int, InstitutionCheckIn> $checkIns
 * @property-read Institution|null $institution
 * @property-read Meeting|null $meeting
 * @property-read Collection<int, Meeting> $meetings
 * @property-read User|null $recipient
 * @property-read User|null $requestedBy
 * @property-read InstitutionActivityRequest|null $resolvedByRequest
 * @property-read Task|null $task
 *
 * @method static \Database\Factories\InstitutionActivityRequestFactory factory($count = null, $state = [])
 * @method static Builder<static>|InstitutionActivityRequest newModelQuery()
 * @method static Builder<static>|InstitutionActivityRequest newQuery()
 * @method static Builder<static>|InstitutionActivityRequest open()
 * @method static Builder<static>|InstitutionActivityRequest query()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
class InstitutionActivityRequest extends Model
{
    /** @use HasFactory<InstitutionActivityRequestFactory> */
    use HasFactory, HasUlids;

    public const int LINK_LIFETIME_DAYS = 14;

    #[\Override]
    protected $attributes = ['campaign_type' => 'activity_confirmation'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'campaign_type' => InstitutionActivityCampaign::class,
            'answer' => InstitutionActivityAnswer::class,
            'answered_at' => 'datetime',
            'resolved_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function checkIn(): BelongsTo
    {
        return $this->belongsTo(InstitutionCheckIn::class, 'check_in_id');
    }

    public function resolvedByRequest(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resolved_by_request_id');
    }

    /**
     * Still waiting for an answer: nobody answered it and the institution's activity was not recorded since.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('answered_at')->whereNull('resolved_at')->where('expires_at', '>', now());
    }

    public function periodEnd(): Carbon
    {
        return $this->period_end ?? today();
    }

    /** @return Collection<int, Meeting> */
    public function incompleteMeetings(): Collection
    {
        if ($this->institution === null) {
            return new Collection;
        }

        return app(MeetingCompletionService::class)->incompleteInPeriod($this->institution->meetings, $this->period_start, $this->periodEnd());
    }

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'institution_activity_request_results', 'request_id', 'meeting_id');
    }

    public function checkIns(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionCheckIn::class, 'institution_activity_request_results', 'request_id', 'check_in_id');
    }

    public function isOpen(): bool
    {
        return $this->answered_at === null && $this->resolved_at === null && $this->expires_at->isFuture();
    }

    /**
     * The page a link in the email opens. It only shows the question; answering is a POST.
     */
    public function answerUrl(?InstitutionActivityAnswer $answer = null): string
    {
        return URL::temporarySignedRoute('activityAnswers.show', $this->expires_at, array_filter([
            'activityRequest' => $this->id,
            'answer' => $answer?->value,
        ]));
    }

    public function submitUrl(): string
    {
        return URL::temporarySignedRoute('activityAnswers.store', $this->expires_at, ['activityRequest' => $this->id]);
    }
}
