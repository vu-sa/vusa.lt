<?php

namespace App\Services\Media;

use App\Support\Media\LegacyImageUrl;
use Normalizer;

/**
 * Finds the file behind a legacy image column value. Reads only; on staging the uploads root is
 * production's storage.
 *
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
class LegacyImageResolver
{
    private const string LOCAL_HOST = '#^https?://(www\.|static\.)?vusa\.lt(?=/)#i';

    private readonly string $uploadsRoot;

    private readonly string $publicRoot;

    public function __construct(?string $uploadsRoot = null, ?string $publicRoot = null)
    {
        $this->uploadsRoot = rtrim($uploadsRoot ?? public_path('uploads'), '/');
        $this->publicRoot = rtrim($publicRoot ?? public_path(), '/');
    }

    public function resolve(?string $value): LegacyImageSource
    {
        $value = trim((string) $value);

        if ($value === '') {
            return new LegacyImageSource(LegacyImageSource::EMPTY);
        }

        if ($value === LegacyImageUrl::PLACEHOLDER) {
            return new LegacyImageSource(LegacyImageSource::PLACEHOLDER);
        }

        // Old news stored a bare sha1 name; those files live in the file manager's news folder.
        if (preg_match('/^[0-9a-f]{40}\.(jpe?g|png)$/i', $value) === 1) {
            return $this->found([$this->uploadsRoot.'/files/news/'.$value]);
        }

        $path = preg_replace(self::LOCAL_HOST, '', $value) ?? $value;

        if (preg_match('#^https?://#i', $path) === 1) {
            return new LegacyImageSource(LegacyImageSource::FOREIGN);
        }

        if (! str_starts_with($path, '/') || str_contains($path, '..')) {
            return new LegacyImageSource(LegacyImageSource::JUNK);
        }

        $path = strtok($path, '?#') ?: $path;

        if (str_starts_with($path, '/uploads/')) {
            $relative = substr($path, strlen('/uploads/'));

            // Mirrors RewriteUploadsUrl: anything not found directly is served from files/.
            return $this->found($this->variants($relative, fn (string $candidate): array => [
                $this->uploadsRoot.'/'.$candidate,
                $this->uploadsRoot.'/files/'.$candidate,
            ]));
        }

        return $this->found($this->variants(ltrim($path, '/'), fn (string $candidate): array => [
            $this->publicRoot.'/'.$candidate,
        ]));
    }

    /**
     * The stored value may be raw, percent-encoded, or in either Unicode normal form.
     *
     * @param  callable(string): list<string>  $locations
     * @return list<string>
     */
    private function variants(string $relative, callable $locations): array
    {
        $decoded = rawurldecode($relative);
        $forms = array_unique(array_filter([
            $relative,
            $decoded,
            Normalizer::normalize($decoded, Normalizer::FORM_C) ?: null,
            Normalizer::normalize($decoded, Normalizer::FORM_D) ?: null,
        ]));

        return array_merge(...array_map($locations, array_values($forms)));
    }

    /**
     * @param  list<string>  $candidates
     */
    private function found(array $candidates): LegacyImageSource
    {
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return new LegacyImageSource(LegacyImageSource::RESOLVED, $candidate);
            }
        }

        return new LegacyImageSource(LegacyImageSource::MISSING);
    }
}
