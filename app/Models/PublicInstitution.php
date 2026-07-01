<?php

namespace App\Models;

use App\Models\Pivots\Relationshipable;
use App\Models\Pivots\Trainable;
use App\Services\PublicInstitutionSearchIndexBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Activity;

/**
 * PublicInstitution - Extends Institution for public display and search indexing
 *
 * This model filters institutions for public visibility based on is_active status.
 * Only active institutions are indexed and searchable in the public contacts search.
 *
 * @property string $id
 * @property array|string|null $name
 * @property array|string|null $short_name
 * @property string $alias
 * @property array|string|null $description
 * @property array|string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $image_url
 * @property string|null $logo_url
 * @property string|null $facebook_url
 * @property string|null $instagram_url
 * @property int|null $tenant_id
 * @property int $is_active
 * @property int $meeting_periodicity_days
 * @property string $contacts_layout
 * @property string|null $selection_method
 * @property array|string|null $appointed_by
 * @property array|string|null $term_length
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activities
 * @property-read Collection<int, FileableFile> $availableFiles
 * @property-read Relationshipable|InstitutionFollow|Trainable|null $pivot
 * @property-read Collection<int, Training> $availableTrainings
 * @property-read Collection<int, InstitutionCheckIn> $checkIns
 * @property-read Collection<int, Comment> $comments
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Duty> $duties
 * @property-read Collection<int, FileableFile> $fileableFiles
 * @property-read Collection<int, User> $followers
 * @property-read bool $has_protocol
 * @property-read bool $has_public_meetings
 * @property-read bool $has_report
 * @property-read mixed $maybe_short_name
 * @property-read mixed $related_institutions
 * @property-read array $translatable_columns_from
 * @property-read Collection<int, Relationship> $incomingRelationships
 * @property-read Collection<int, Meeting> $meetings
 * @property-read Collection<int, Relationship> $outgoingRelationships
 * @property-read Collection<int, Problem> $problems
 * @property-read Collection<int, Comment> $rootComments
 * @property-read Collection<int, Task> $tasks
 * @property-read Collection<int, Task> $tasksFromMeetings
 * @property-read Tenant|null $tenant
 * @property-read mixed $translations
 * @property-read Collection<int, Type> $types
 * @property-read Collection<int, User> $users
 * @property-read int|null $tasks_from_meetings_count
 * @property-read int|null $users_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution hasActiveDuties()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution whereLocale(string $column, string $locale)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution whereLocales(string $column, array $locales)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicInstitution withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PublicInstitution extends Institution
{
    use Searchable;

    /**
     * The table associated with the model.
     * Uses parent Institution table since PublicInstitution is a filtered view.
     *
     * @var string
     */
    protected $table = 'institutions';

    /**
     * Get the class name for polymorphic relations.
     * This ensures we use the parent Institution morph class for typeables lookup.
     */
    public function getMorphClass(): string
    {
        return Institution::class;
    }

    /**
     * Override types relationship to use correct morph name
     * Laravel would default to 'public_institution' based on model name
     */
    public function types(): MorphToMany
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    /**
     * Override duties relationship to use correct foreign key
     * Laravel would default to 'public_institution_id' based on model name
     */
    public function duties(): HasMany
    {
        return $this->hasMany(Duty::class, 'institution_id', 'id');
    }

    /**
     * Override meetings relationship to use correct pivot table
     * Laravel would default to 'institution_public_institution' based on model name
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'institution_meeting', 'institution_id', 'meeting_id');
    }

    /**
     * Determine if institution should be indexed for public search
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active === 1;
    }

    /**
     * Get searchable array for Typesense indexing.
     * Delegates to PublicInstitutionSearchIndexBuilder.
     */
    public function toSearchableArray(): array
    {
        return app(PublicInstitutionSearchIndexBuilder::class)->build($this);
    }

    /**
     * Get the index name for the model
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').'public_institutions';
    }

    /**
     * Get the engine used to index the model
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('typesense');
    }
}
