<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class PublicAssetService
{
    public function __construct(private readonly ?string $cataloguePath = null) {}

    public function logoSrc(?string $tenantAlias, string $locale): string
    {
        $language = $locale === 'en' ? 'en' : 'lt';
        $alias = in_array($tenantAlias, [
            'chgf', 'evaf', 'ff', 'filf', 'fsf', 'gmc', 'if', 'kf', 'knf',
            'mf', 'mif', 'sa', 'tf', 'tspmi', 'vm', 'vusa',
        ], true) ? $tenantAlias : 'vusa';

        if ($alias === 'chgf' && $language === 'lt') {
            return '/logos/hor/lt/vusachgf.lin.hor.balt.svg';
        }

        if ($alias === 'sa') {
            return "/logos/hor/{$language}/vusasa.lin.hor.tams.{$language}.svg";
        }

        $filename = $alias === 'vusa' ? 'vusa' : "vusa{$alias}";

        return "/logos/hor/{$language}/{$filename}.lin.hor.tams.svg";
    }

    /** @return array<string, array<string, mixed>> */
    public function icons(array $names): array
    {
        if ($names === []) {
            return [];
        }

        $path = $this->cataloguePath ?? base_path('bootstrap/icons/fluent-icons.json');

        if (! File::exists($path)) {
            return [];
        }

        sort($names);
        $version = File::exists($path.'.version')
            ? File::get($path.'.version')
            : File::lastModified($path).'-'.File::size($path);
        $key = 'public_icons_'.hash('sha256', $path.$version.json_encode($names));

        return Cache::remember($key, 3600, function () use ($path, $names): array {
            $catalogue = json_decode(File::get($path), true);

            if (! is_array($catalogue)) {
                return [];
            }

            return array_filter(
                array_intersect_key($catalogue, array_flip($names)),
                fn ($icon) => is_array($icon) && is_string($icon['body'] ?? null)
            );
        });
    }
}
