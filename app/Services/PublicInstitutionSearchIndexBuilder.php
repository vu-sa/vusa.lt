<?php

namespace App\Services;

use App\Models\PublicInstitution;

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
        ]);

        $activeDutiesCount = $institution->duties()
            ->whereHas('current_users')
            ->count();

        return [
            'id' => $institution->id,
            'title' => $institution->getTranslation('name', 'lt'),
            'name_lt' => $institution->getTranslation('name', 'lt'),
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

            // Media
            'image_url' => $institution->image_url,
            'logo_url' => $institution->logo_url,
            'has_logo' => ! empty($institution->logo_url),
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

            // Stats
            'duties_count' => $activeDutiesCount,
            'has_contacts' => $activeDutiesCount > 0,

            // For sorting
            'created_at' => $institution->created_at->timestamp,
            'updated_at' => $institution->updated_at->timestamp,
        ];
    }
}
