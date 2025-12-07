<?php

namespace App\Models;

use Laravel\Scout\Searchable;

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
 * @property string $contacts_layout
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read \App\Models\Pivots\Relationshipable|\App\Models\Pivots\Trainable|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Training> $availableTrainings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InstitutionCheckIn> $checkIns
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Document> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Duty> $duties
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SharepointFile> $files
 * @property-read bool $has_public_meetings
 * @property-read mixed $maybe_short_name
 * @property-read mixed $related_institutions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relationship> $incomingRelationships
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Meeting> $meetings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Relationship> $outgoingRelationships
 * @property-read \App\Models\Tenant|null $tenant
 * @property-read mixed $translations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Type> $types
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 *
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
    public function types(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(Type::class, 'typeable');
    }

    /**
     * Override duties relationship to use correct foreign key
     * Laravel would default to 'public_institution_id' based on model name
     */
    public function duties(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Duty::class, 'institution_id', 'id');
    }

    /**
     * Override meetings relationship to use correct pivot table
     * Laravel would default to 'institution_public_institution' based on model name
     */
    public function meetings(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'institution_meeting', 'institution_id', 'meeting_id');
    }

    /**
     * Determine if institution should be indexed for public search
     */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active === 1 || $this->is_active === true;
    }

    /**
     * Get searchable array for Typesense indexing
     */
    public function toSearchableArray(): array
    {
        // Load required relationships
        $this->loadMissing([
            'tenant',
            'types',
        ]);

        // Get number of current duty holders
        $activeDutiesCount = $this->duties()
            ->whereHas('current_users')
            ->count();

        return [
            'id' => $this->id,
            'title' => $this->getTranslation('name', 'lt'), // For InstantSearch compatibility
            'name_lt' => $this->getTranslation('name', 'lt'),
            'name_en' => $this->getTranslation('name', 'en'),
            'short_name_lt' => $this->getTranslation('short_name', 'lt'),
            'short_name_en' => $this->getTranslation('short_name', 'en'),
            'alias' => $this->alias,

            // Contact info
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address_lt' => $this->getTranslation('address', 'lt'),
            'address_en' => $this->getTranslation('address', 'en'),

            // Media
            'image_url' => $this->image_url,
            'logo_url' => $this->logo_url,
            'has_logo' => !empty($this->logo_url),
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,

            // Tenant info
            'tenant_id' => $this->tenant?->id,
            'tenant_shortname' => $this->tenant?->shortname,
            'tenant_alias' => $this->tenant?->alias,
            'tenant_type' => $this->tenant?->type,

            // Types for filtering
            'type_ids' => $this->types->pluck('id')->toArray(),
            'type_slugs' => $this->types->pluck('slug')->toArray(),
            'type_titles_lt' => $this->types->map(fn ($t) => $t->getTranslation('title', 'lt'))->filter()->toArray(),
            'type_titles_en' => $this->types->map(fn ($t) => $t->getTranslation('title', 'en'))->filter()->toArray(),

            // Stats
            'duties_count' => $activeDutiesCount,
            'has_contacts' => $activeDutiesCount > 0,

            // For sorting
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }

    /**
     * Get the index name for the model
     */
    public function searchableAs(): string
    {
        return 'public_institutions';
    }

    /**
     * Get the engine used to index the model
     */
    public function searchableUsing()
    {
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
    }
}
