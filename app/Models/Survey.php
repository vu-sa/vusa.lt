<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Enums\SurveyStatus;
use App\Jobs\PublishSurveyToLimeSurveyJob;
use App\Models\Traits\HasApprovals;
use App\Models\Traits\HasTranslations;
use App\Models\Traits\LogsModelActivity;
use App\Services\ModelAuthorizer;
use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A survey drafted and approved in vusa.lt, delivered by LimeSurvey.
 *
 * The lifecycle gate is the shared approval system: a survey is only pushed to LimeSurvey
 * from onApprovalComplete(). This ordering is not cosmetic — LimeSurvey locks the question
 * structure of an activated survey, so approval has to happen while changes are still
 * possible.
 *
 * No response data is held here. `response_stats` is aggregate counters pulled back from
 * LimeSurvey; individual answers never leave it.
 *
 * @property string $id
 * @property int $tenant_id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $welcome_text
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_anonymous
 * @property SurveyStatus $status
 * @property int|null $limesurvey_survey_id
 * @property string|null $limesurvey_url
 * @property string|null $sync_status
 * @property string|null $sync_error_message
 * @property array<array-key, mixed>|null $response_stats
 * @property Carbon|null $stats_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant|null $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SurveyQuestion> $questions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Approval> $approvals
 *
 * @method static SurveyFactory factory($count = null, $state = [])
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'tenant_id', 'name', 'description', 'welcome_text',
    'starts_at', 'ends_at', 'is_anonymous', 'status',
])]
class Survey extends Model implements Approvable
{
    use HasApprovals, HasFactory, HasTranslations, HasUlids, LogsModelActivity, SoftDeletes;

    /** @var array<int, string> */
    public $translatable = ['name', 'description', 'welcome_text'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => SurveyStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_anonymous' => 'boolean',
            'response_stats' => 'array',
            'stats_synced_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Alias for the policy layer, which may load either name depending on the model.
     */
    public function tenants(): BelongsTo
    {
        return $this->tenant();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order');
    }

    /**
     * Whether this survey exists in LimeSurvey.
     */
    public function isPublished(): bool
    {
        return $this->limesurvey_survey_id !== null;
    }

    /**
     * Whether questions and settings may still be changed.
     *
     * Both halves matter: the status gate is ours, the limesurvey_survey_id gate mirrors
     * LimeSurvey's own structure lock on an activated survey.
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable() && ! $this->isPublished();
    }

    // =========================================================================
    // Approvable Contract Implementation
    // =========================================================================

    /**
     * React to an approval decision.
     *
     * Approval does not publish directly — it marks the survey approved and hands off to a
     * queued job, so a slow or unreachable LimeSurvey never blocks the approver's request.
     */
    public function onApprovalComplete(ApprovalDecision $decision, int $step): void
    {
        match ($decision) {
            ApprovalDecision::Approved => $this->handleApproved(),
            ApprovalDecision::Rejected => $this->forceFill(['status' => SurveyStatus::Rejected])->save(),
            ApprovalDecision::Cancelled => $this->forceFill(['status' => SurveyStatus::Draft])->save(),
        };
    }

    private function handleApproved(): void
    {
        $this->forceFill([
            'status' => SurveyStatus::Approved,
            'sync_status' => 'pending',
            'sync_error_message' => null,
        ])->save();

        PublishSurveyToLimeSurveyJob::dispatch($this);
    }

    /**
     * Users who may decide at the given step: whoever holds that step's permission for
     * this survey's tenant.
     *
     * @return Collection<int, User>
     */
    public function getApproversForStep(int $step): Collection
    {
        $permission = $this->getApprovalFlow()?->getPermissionForStep($step);

        if ($permission === null) {
            return collect();
        }

        /** @var Collection<int, User> */
        return User::query()
            ->whereHas('duties.roles.permissions', fn ($q) => $q->where('name', $permission))
            ->get()
            ->filter(fn (User $user): bool => $this->userCoversTenant($user, $permission))
            ->values();
    }

    public function getApprovalFlow(): ?ApprovalFlow
    {
        return ApprovalFlow::query()
            ->where('flowable_type', self::class)
            ->whereNull('flowable_id')
            ->first();
    }

    public function getApprovalDisplayName(): string
    {
        return (string) $this->name;
    }

    public function getApprovalUrl(): string
    {
        return route('surveys.show', $this->id);
    }

    /**
     * Only a survey actually waiting on a decision can receive one.
     */
    public function isDecisionAllowed(ApprovalDecision $decision): bool
    {
        return $this->status === SurveyStatus::PendingApproval;
    }

    public function canBeApprovedBy(User $user, ?int $step = null, $decision = null): bool
    {
        $permission = $this->getApprovalFlow()?->getPermissionForStep($step ?? $this->currentApprovalStep());

        if ($permission === null) {
            return false;
        }

        return $this->userCoversTenant($user, $permission);
    }

    /**
     * Whether the user holds the permission in a scope that reaches this survey's tenant.
     *
     * A global ('*') permission covers everything; a padalinys-scoped one only covers the
     * tenants the user actually works for.
     */
    private function userCoversTenant(User $user, string $permission): bool
    {
        $authorizer = app(ModelAuthorizer::class);

        if (! $authorizer->forUser($user)->check($permission)) {
            return false;
        }

        if (str_ends_with($permission, '.*')) {
            return true;
        }

        return $authorizer->getTenants()->contains(fn ($tenant): bool => $tenant->id === $this->tenant_id);
    }
}
