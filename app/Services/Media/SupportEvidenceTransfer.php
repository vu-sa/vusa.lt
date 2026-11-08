<?php

namespace App\Services\Media;

use App\Models\SupportRequest;
use App\Support\StagingProtection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/** Transitional(private-evidence): remove when media:privatize-evidence --status reports no pending files or public copies. */
class SupportEvidenceTransfer
{
    private const string COPIES = 'evidence_public_copies';

    /** @return Builder<Media> */
    public function records(): Builder
    {
        return Media::query()->whereIn('model_type', [(new SupportRequest)->getMorphClass(), SupportRequest::class])
            ->where('collection_name', 'evidence');
    }

    public function pending(Media $media): bool
    {
        return $media->disk !== 'supportEvidence' || $media->conversions_disk !== 'supportEvidence';
    }

    public function hasPublicCopies(Media $media): bool
    {
        return $media->hasCustomProperty(self::COPIES);
    }

    public function transfer(int $id, bool $cleanup = false): void
    {
        DB::transaction(function () use ($id, $cleanup): void {
            $media = $this->records()->lockForUpdate()->findOrFail($id);

            if ($this->pending($media)) {
                $this->copy($media);
            }

            if ($cleanup) {
                $this->cleanup($media);
            }
        });
    }

    /** @return list<array{disk: string, path: string}> */
    public function cleanupFiles(Media $media): array
    {
        $copies = $media->getCustomProperty(self::COPIES, []);
        if ($this->pending($media)) {
            foreach ($this->sourceFiles($media) as $disk => $files) {
                if ($disk !== 'supportEvidence') {
                    foreach ($files as $path) {
                        $copies[] = ['disk' => $disk, 'path' => $path];
                    }
                }
            }
        }

        return collect($copies)
            ->filter(fn (array $copy): bool => Storage::disk($copy['disk'])->exists($copy['path']))
            ->unique(fn (array $copy): string => $copy['disk'].'/'.$copy['path'])
            ->map(fn (array $copy): array => ['disk' => $copy['disk'], 'path' => $copy['path']])
            ->values()->all();
    }

    /** @return array<string, list<string>> */
    private function sourceFiles(Media $media): array
    {
        $paths = PathGeneratorFactory::create($media);
        $sources = [$media->disk => Storage::disk($media->disk)->files($paths->getPath($media))];
        $conversionDisk = $media->conversions_disk ?: $media->disk;
        $sources[$conversionDisk] = array_values(array_unique([
            ...($sources[$conversionDisk] ?? []),
            ...Storage::disk($conversionDisk)->allFiles($paths->getPathForConversions($media)),
            ...Storage::disk($conversionDisk)->allFiles($paths->getPathForResponsiveImages($media)),
        ]));

        return $sources;
    }

    private function copy(Media $media): void
    {
        StagingProtection::ensureDiskIsWritable('supportEvidence');
        if (! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
            throw new RuntimeException("Original missing for media {$media->id}.");
        }

        $sources = $this->sourceFiles($media);
        $private = Storage::disk('supportEvidence');
        $copies = $media->getCustomProperty(self::COPIES, []);

        foreach ($sources as $disk => $files) {
            if ($disk === 'supportEvidence') {
                continue;
            }

            foreach ($files as $path) {
                $contents = Storage::disk($disk)->get($path);
                $hash = hash('sha256', $contents);
                $private->put($path, $contents);
                if (hash('sha256', $private->get($path)) !== $hash) {
                    throw new RuntimeException("Copy verification failed for media {$media->id}.");
                }
                $copies[] = ['disk' => $disk, 'path' => $path, 'hash' => $hash];
            }
        }

        $media->disk = 'supportEvidence';
        $media->conversions_disk = 'supportEvidence';
        $media->setCustomProperty(self::COPIES, $copies);
        $media->saveQuietly();
    }

    private function cleanup(Media $media): void
    {
        $copies = $media->getCustomProperty(self::COPIES, []);
        foreach ($copies as $copy) {
            StagingProtection::ensureDiskIsWritable($copy['disk']);
            $source = Storage::disk($copy['disk']);
            if (! $source->exists($copy['path'])) {
                continue;
            }

            if (hash('sha256', $source->get($copy['path'])) !== $copy['hash']
                || hash('sha256', Storage::disk('supportEvidence')->get($copy['path'])) !== $copy['hash']) {
                throw new RuntimeException("File changed for media {$media->id}; public copy retained.");
            }
        }

        foreach ($copies as $copy) {
            $source = Storage::disk($copy['disk']);
            if ($source->exists($copy['path']) && ! $source->delete($copy['path'])) {
                throw new RuntimeException("Could not remove public copy for media {$media->id}.");
            }
        }

        $media->forgetCustomProperty(self::COPIES);
        $media->saveQuietly();
    }
}
