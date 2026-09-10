<?php

namespace App\Models;

use App\Contracts\Commentable;
use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Models\Traits\HasComments;
use App\Models\Traits\LogsModelActivity;
use Database\Factories\SupportRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string $id
 * @property string|null $created_by
 * @property string|null $assigned_to
 * @property int $support_service_id
 * @property int $support_request_type_id
 * @property int $support_request_area_id
 * @property string|null $reporter_name
 * @property string|null $reporter_email
 * @property SupportRequestVisibility $visibility
 * @property SupportRequestStatus $status
 * @property string $title
 * @property string $description
 * @property string|null $context_url
 * @property string|null $selected_text
 * @property string $locale
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read SupportRequestArea $area
 * @property-read User|null $assignedTo
 * @property-read Collection<int, Comment> $comments
 * @property-read User|null $creator
 * @property-read MediaCollection<int, Media> $media
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Comment> $rootComments
 * @property-read SupportService $service
 * @property-read SupportRequestType $type
 *
 * @method static \Database\Factories\SupportRequestFactory factory($count = null, $state = [])
 * @method static Builder<static>|SupportRequest newModelQuery()
 * @method static Builder<static>|SupportRequest newQuery()
 * @method static Builder<static>|SupportRequest onlyTrashed()
 * @method static Builder<static>|SupportRequest open()
 * @method static Builder<static>|SupportRequest query()
 * @method static Builder<static>|SupportRequest resolved()
 * @method static Builder<static>|SupportRequest withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|SupportRequest withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'created_by',
    'assigned_to',
    'support_service_id',
    'support_request_type_id',
    'support_request_area_id',
    'reporter_name',
    'reporter_email',
    'visibility',
    'status',
    'title',
    'description',
    'context_url',
    'selected_text',
    'locale',
    'resolved_at',
])]
class SupportRequest extends Model implements Commentable, HasMedia
{
    /** @use HasFactory<SupportRequestFactory> */
    use HasComments, HasFactory, HasUlids, InteractsWithMedia, LogsModelActivity, Searchable, SoftDeletes;

    #[\Override]
    protected $attributes = [
        'status' => 'new',
        'visibility' => 'private',
        'locale' => 'lt',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportRequestStatus::class,
            'visibility' => SupportRequestVisibility::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(SupportService::class, 'support_service_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(SupportRequestType::class, 'support_request_type_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(SupportRequestArea::class, 'support_request_area_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_support_request');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('evidence')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('spatieMediaLibrary');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->width(400)
            ->height(300);

        $this->addMediaConversion('preview')
            ->nonQueued()
            ->width(1200)
            ->height(900);
    }

    public function isResolved(): bool
    {
        return $this->status->isTerminal() && ! is_null($this->resolved_at);
    }

    public function isNew(): bool
    {
        return $this->status === SupportRequestStatus::New;
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->whereNotIn('status', [SupportRequestStatus::Done, SupportRequestStatus::Declined]);
    }

    #[Scope]
    protected function resolved(Builder $query): Builder
    {
        return $query->whereIn('status', [SupportRequestStatus::Done, SupportRequestStatus::Declined]);
    }

    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
        ];
    }
}
