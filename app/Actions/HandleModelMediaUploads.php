<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Handle model media uploads with consistent patterns.
 *
 * Supports:
 * - Single file collections (singleFile() automatically replaces)
 * - Multiple file collections with add/delete handling
 *
 * Usage:
 *   HandleModelMediaUploads::execute($model, $request, [
 *       'main_image' => ['collection' => 'main_image', 'single' => true],
 *       'images' => ['collection' => 'images', 'single' => false],
 *   ]);
 */
class HandleModelMediaUploads
{
    /**
     * Execute media uploads for a model.
     *
     * @param  HasMedia  $model  The model to attach media to
     * @param  Request  $request  The request containing files
     * @param  array  $config  Configuration for each media field
     *                         Format: ['field_name' => ['collection' => 'collection_name', 'single' => bool]]
     */
    public static function execute(HasMedia $model, Request $request, array $config): void
    {
        foreach ($config as $fieldName => $options) {
            $collection = $options['collection'] ?? $fieldName;
            $isSingle = $options['single'] ?? false;

            if ($isSingle) {
                self::handleSingleFile($model, $request, $fieldName, $collection);
            } else {
                self::handleMultipleFiles($model, $request, $fieldName, $collection);
            }
        }
    }

    /**
     * Handle single file upload (replaces existing via singleFile() collection setting).
     */
    protected static function handleSingleFile(
        HasMedia $model,
        Request $request,
        string $fieldName,
        string $collection
    ): void {
        if (! $request->hasFile($fieldName)) {
            return;
        }

        $file = $request->file($fieldName);

        $model->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->toMediaCollection($collection);
    }

    /**
     * Handle multiple file uploads.
     */
    protected static function handleMultipleFiles(
        HasMedia $model,
        Request $request,
        string $fieldName,
        string $collection
    ): void {
        $files = $request->file($fieldName);

        if (! $files) {
            return;
        }

        // Handle both array of files and array of ['file' => File] structures
        /** @var array<int|string, \Illuminate\Http\UploadedFile|array<string, mixed>> $filesArray */
        $filesArray = is_array($files) ? $files : [$files];

        foreach ($filesArray as $fileData) {
            /** @var \Illuminate\Http\UploadedFile|array<string, mixed>|null $file */
            $file = is_array($fileData) ? ($fileData['file'] ?? $fileData) : $fileData;

            if (! $file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }

            $model->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->withCustomProperties(['alt' => ''])
                ->toMediaCollection($collection);
        }
    }

    /**
     * Delete media items that are no longer in the submitted list.
     *
     * Useful for update operations where you need to sync media.
     *
     * @param  HasMedia  $model  The model with media
     * @param  string  $collection  The media collection name
     * @param  array  $keepIds  IDs of media items to keep
     */
    public static function deleteRemovedMedia(HasMedia $model, string $collection, array $keepIds): void
    {
        $model->getMedia($collection)
            ->filter(fn (Media $media) => ! in_array($media->id, $keepIds))
            ->each(fn (Media $media) => $media->delete());
    }

    /**
     * Delete a single media item from a collection.
     */
    public static function deleteMedia(HasMedia $model, string $collection, int $mediaId): bool
    {
        $media = $model->getMedia($collection)->firstWhere('id', $mediaId);

        if (! $media) {
            return false;
        }

        $media->delete();

        return true;
    }
}
