<?php

namespace App\Services;

use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\PublicInstitution;
use App\Settings\AtstovavimasSettings;
use Illuminate\Support\Str;

class PublicInstitutionSearchIndexBuilder
{
    /**
     * Build a searchable array for a PublicInstitution model.
     */
    public function build(PublicInstitution $institution): array
    {
        $institution->loadMissing([
            'tenant',
            'types',
            'media',
        ]);

        $duties = $institution->duties()
            ->with(['current_users' => function ($q): void {
                $q->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.profile_photo_path',
                    'users.profile_photo_focal_point',
                ]);
            }])
            ->orderBy('order')
            ->get();

        $contacts = [];
        $currentUserNames = [];

        foreach ($duties as $duty) {
            foreach ($duty->current_users as $user) {
                $currentUserNames[] = $user->name;

                $contacts[] = [
                    'id' => (string) $user->id,
                    'name' => (string) $user->name,
                    'duty_id' => (string) $duty->id,
                    'duty_name' => (string) $duty->getTranslation('name', 'lt'),
                    // Same precedence as the card on database-backed pages: duty mailbox, term address, personal address.
                    'email' => $duty->email ?: ($user->pivot?->additional_email ?: $user->email),
                    'profile_photo_path' => $user->profile_photo_path ? (string) $user->profile_photo_path : null,
                    'profile_photo_focal_point' => $user->profile_photo_focal_point ? (string) $user->profile_photo_focal_point : null,
                    'additional_photo' => $user->pivot?->additional_photo ? (string) $user->pivot->additional_photo : null,
                    'additional_photo_focal_point' => $user->pivot?->additional_photo_focal_point ? (string) $user->pivot->additional_photo_focal_point : null,
                ];
            }
        }

        $currentUserNames = array_values(array_unique(array_filter($currentUserNames)));
        $activeDutiesCount = $duties->filter(fn (Duty $d) => $d->current_users->isNotEmpty())->count();

        $atstovavimasSettings = app(AtstovavimasSettings::class);
        $isStudentRepresentation = $atstovavimasSettings->isStudentRepresentativeInstitution($institution);

        return [
            'id' => $institution->id,
            'title' => $institution->getTranslation('name', 'lt') ?? '',
            'name_lt' => $institution->getTranslation('name', 'lt') ?? '',
            'name_en' => $institution->getTranslation('name', 'en'),
            'short_name_lt' => $institution->getTranslation('short_name', 'lt'),
            'short_name_en' => $institution->getTranslation('short_name', 'en'),
            'alias' => $institution->alias,

            // Contact info
            'email' => $institution->email,
            'phone' => $institution->phone,
            'website' => $institution->website,
            'address_lt' => $institution->getTranslation('address', 'lt'),
            'address_en' => $institution->getTranslation('address', 'en'),
            'description_lt' => $this->excerpt($institution->getTranslation('description', 'lt')),
            'description_en' => $this->excerpt($institution->getTranslation('description', 'en')),

            // Media
            'image_url' => $institution->imageData('image')['url'] ?? null,
            'logo_url' => $institution->imageData('logo')['thumb'] ?? null,
            'has_logo' => $institution->imageData('logo') !== null,
            'facebook_url' => $institution->facebook_url,
            'instagram_url' => $institution->instagram_url,

            // Tenant info
            'tenant_id' => $institution->tenant?->id,
            'tenant_shortname' => $institution->tenant?->shortname,
            'tenant_alias' => $institution->tenant?->alias,
            'tenant_type' => $institution->tenant?->type,

            // Types for filtering
            'type_ids' => $institution->types->pluck('id')->toArray(),
            'type_slugs' => $institution->types->pluck('slug')->toArray(),
            'type_titles_lt' => $institution->types->map(fn ($t) => $t->getTranslation('title', 'lt'))->filter()->toArray(),
            'type_titles_en' => $institution->types->map(fn ($t) => $t->getTranslation('title', 'en'))->filter()->toArray(),

            // Representation & contacts
            'is_student_representation' => $isStudentRepresentation,
            'contacts' => $contacts,
            'current_user_names' => $currentUserNames,

            'display_priority' => $this->displayPriority($institution),

            // Stats
            'duties_count' => $activeDutiesCount,
            'has_contacts' => $activeDutiesCount > 0,

            // For sorting
            'created_at' => $institution->created_at->timestamp,
            'updated_at' => $institution->updated_at->timestamp,
        ];
    }

    /**
     * Browse order for the contacts search: VU SA, its governing bodies, then each
     * padalinys' and PKP's main institution; everything else ties at zero.
     */
    private function displayPriority(PublicInstitution $institution): int
    {
        $tenant = $institution->tenant;
        $isPrimary = $tenant !== null && (string) $tenant->primary_institution_id === (string) $institution->id;

        return match (true) {
            $isPrimary && $tenant->type === TenantType::Pagrindinis => 4,
            $tenant?->type === TenantType::Pagrindinis
                && $institution->types->contains('slug', config('vusa.featured_institution_type')) => 3,
            $isPrimary && $tenant->type === TenantType::Padalinys => 2,
            $isPrimary && $tenant->type === TenantType::Pkp => 1,
            default => 0,
        };
    }

    /**
     * Plain text for the contact card; the full rich description stays on the institution page.
     */
    private function excerpt(?string $html): ?string
    {
        // Block boundaries become spaces so adjacent paragraphs don't fuse into one word.
        $spaced = preg_replace('/<(br|\/(p|div|li|h[1-6]|blockquote|tr|td))\b[^>]*>/i', ' ', (string) $html);
        $text = Str::squish(html_entity_decode(strip_tags((string) $spaced), ENT_QUOTES | ENT_HTML5));

        return $text === '' ? null : Str::limit($text, 400);
    }
}
