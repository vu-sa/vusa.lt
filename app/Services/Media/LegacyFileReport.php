<?php

namespace App\Services\Media;

use App\Services\FileUsageScanner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Which legacy image files nothing references any more: everything in the former shared form
 * folders, plus file manager files that were copied into media. Only those copies; the rest of the
 * file manager is not this report's business.
 *
 * Transitional(legacy-images): remove once the legacy folders have been cleaned up.
 */
class LegacyFileReport
{
    /** The folders `files.uploadImage` wrote form images into. */
    public const array SHARED_FOLDERS = ['banners', 'calendar', 'contacts', 'institutions', 'news', 'pages', 'resources', 'uploads'];

    /** A reference the scanner cannot see (bare file names) may hide in these until Release 2 drops them. */
    public const array LEGACY_COLUMNS = [
        'news' => 'image',
        'banners' => 'image_url',
        'institutions' => 'image_url',
        'pages' => 'featured_image',
    ];

    private readonly string $uploadsRoot;

    public function __construct(private readonly FileUsageScanner $scanner, ?string $uploadsRoot = null)
    {
        $this->uploadsRoot = rtrim($uploadsRoot ?? public_path('uploads'), '/');
    }

    /**
     * @return array<string, array{files: int, unreferenced: list<string>, bytes: int}>
     */
    public function build(?string $onlyGroup = null, ?callable $progress = null): array
    {
        $report = [];

        foreach ($this->candidates($onlyGroup, $progress) as $group => $paths) {
            $unreferenced = [];
            foreach ($paths as $index => $path) {
                $referenced = $this->isReferenced($path);
                if (! $referenced) {
                    $unreferenced[] = $path;
                }
                if ($progress !== null) {
                    $progress($group, $index + 1, count($paths), $path, $referenced);
                }
            }

            $report[$group] = [
                'files' => count($paths),
                'unreferenced' => $unreferenced,
                'bytes' => array_sum(array_map(fn (string $path): int => (int) @filesize($this->uploadsRoot.'/'.$path), $unreferenced)),
            ];
        }

        return $report;
    }

    /**
     * File manager files go through the full scan, which also knows the `/uploads/<path>` form
     * RewriteUploadsUrl redirects into `files/`.
     */
    private function isReferenced(string $path): bool
    {
        return str_starts_with($path, 'files/')
            ? $this->scanner->scanFileUsage($path)['total_usages'] > 0
            : $this->scanner->isReferencedBelowUploads($path);
    }

    /**
     * Why deleting is not safe yet; empty when it is.
     *
     * @return list<string>
     */
    public function deletionBlockers(): array
    {
        $blockers = [];

        foreach (self::LEGACY_COLUMNS as $table => $column) {
            if (Schema::hasColumn($table, $column)) {
                $blockers[] = "{$table}.{$column} still exists; drop the legacy columns (Release 2) first.";
            }
        }

        return $blockers;
    }

    public function delete(string $pathBelowUploads): bool
    {
        $path = $this->uploadsRoot.'/'.ltrim($pathBelowUploads, '/');

        return ! str_contains($pathBelowUploads, '..') && File::isFile($path) && File::delete($path);
    }

    /**
     * @return \Generator<string, list<string>> paths below the uploads root, grouped by folder
     */
    private function candidates(?string $onlyGroup, ?callable $progress): \Generator
    {
        foreach ([...self::SHARED_FOLDERS, 'files'] as $folder) {
            if ($onlyGroup !== null && $folder !== $onlyGroup) {
                continue;
            }
            if ($progress !== null) {
                $progress($folder, 0, 0, null, null);
            }
            if ($folder === 'files') {
                yield $folder => $this->copiedFileManagerFiles();

                continue;
            }
            $directory = $this->uploadsRoot.'/'.$folder;

            yield $folder => File::isDirectory($directory)
                ? array_map(fn (SplFileInfo $file): string => $folder.'/'.$file->getRelativePathname(), File::allFiles($directory))
                : [];
        }
    }

    /**
     * @return list<string>
     */
    private function copiedFileManagerFiles(): array
    {
        $resolver = new LegacyImageResolver($this->uploadsRoot);
        $paths = [];

        Media::query()->whereNotNull('custom_properties->legacy_source')->lazyById(500)->each(function (Media $media) use ($resolver, &$paths): void {
            $source = $resolver->resolve((string) $media->getCustomProperty('legacy_source'));
            $prefix = $this->uploadsRoot.'/files/';

            if ($source->isResolved() && str_starts_with((string) $source->path, $prefix)) {
                $paths['files/'.substr((string) $source->path, strlen($prefix))] = true;
            }
        });

        return array_keys($paths);
    }
}
