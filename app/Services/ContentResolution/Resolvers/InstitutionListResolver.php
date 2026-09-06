<?php

namespace App\Services\ContentResolution\Resolvers;

use App\Models\ContentPart;
use App\Models\Institution;
use App\Models\Type;
use App\Services\ContentResolution\ResolutionContext;
use App\Services\ContentResolution\ResolvesContentPart;
use Illuminate\Support\Collection;

/**
 * Resolves each institution-list block independently based on its typeSlug, tenantScope,
 * limit and order options.
 */
final class InstitutionListResolver implements ResolvesContentPart
{
    private const int MAX_LIMIT = 50;

    private const int MAX_TENANT_IDS = 30;

    public function resolve(Collection $parts, ResolutionContext $context): array
    {
        $resolved = [];
        foreach ($parts as $id => $part) {
            $resolved[$id] = $this->resolvePart($part, $context);
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePart(ContentPart $part, ResolutionContext $context): array
    {
        $options = (array) ($part->options ?? []);
        $typeSlug = $options['typeSlug'] ?? null;
        $order = $options['order'] ?? 'name';
        $limit = isset($options['limit']) && is_numeric($options['limit'])
            ? max(1, min(self::MAX_LIMIT, (int) $options['limit']))
            : null;

        $query = Institution::query()
            ->where('is_active', true)
            ->with([
                'tenant:id,shortname,alias,type',
                'types:id,slug,title',
            ]);

        if (is_string($typeSlug) && $typeSlug !== '') {
            $query->whereHas('types', fn ($q) => $q->where('slug', $typeSlug));
        }

        $tenantScope = $options['tenantScope'] ?? 'all';
        if (is_array($tenantScope)) {
            $ids = array_slice(array_map(intval(...), array_filter($tenantScope, is_numeric(...))), 0, self::MAX_TENANT_IDS);
            $query->whereIn('tenant_id', $ids);
        } elseif ($tenantScope === 'current') {
            $query->where('tenant_id', $context->tenant->id);
        }

        if ($order === 'order') {
            $query->orderBy('name');
        } else {
            $query->orderBy('name');
        }

        if ($limit !== null) {
            $query->take($limit);
        }

        $institutions = $query->get();

        $items = $institutions->map(function (Institution $institution): array {
            $tenant = $institution->tenant;
            $tenantType = $tenant?->type;
            $tenantTypeValue = $tenantType?->value;

            return [
                'id' => $institution->id,
                'name' => $institution->name,
                'short_name' => $institution->short_name,
                'alias' => $institution->alias,
                'description' => $institution->description ? strip_tags((string) $institution->description) : null,
                'email' => $institution->email,
                'phone' => $institution->phone,
                'website' => $institution->website,
                'image_url' => $institution->image_url,
                'logo_url' => $institution->logo_url,
                'facebook_url' => $institution->facebook_url,
                'instagram_url' => $institution->instagram_url,
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'shortname' => $tenant->shortname,
                    'alias' => $tenant->alias,
                    'type' => $tenantTypeValue ?? $tenantType,
                ] : null,
                'types' => $institution->types->map(fn (Type $type) => [
                    'id' => $type->id,
                    'slug' => $type->slug,
                    'title' => $type->title,
                ])->values()->all(),
            ];
        })->all();

        return [
            'type' => 'institution-list',
            'items' => $items,
            'meta' => ['total' => count($items)],
        ];
    }
}
