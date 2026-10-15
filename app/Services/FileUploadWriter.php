<?php

namespace App\Services;

use App\Support\StagingProtection;
use App\Support\StoragePath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FileUploadWriter
{
    /** @return array{name: string, path: string, url: string, renamed: bool} */
    public function write(string $directory, string $filename, UploadedFile|string $contents): array
    {
        StagingProtection::ensureFilesAreWritable();

        if (! str_starts_with($directory, 'public/')
            || StoragePath::normalizeRelative($directory) !== $directory
            || ! StoragePath::isSafeRelative($directory)
            || ! StoragePath::isSafeRelative($filename)
            || str_contains($filename, '/') || str_contains($filename, '\\')
            || in_array($filename, ['.', '..'], true)) {
            throw new \InvalidArgumentException('Invalid upload destination');
        }

        // Allocation and writing share a lock, including image uploads with converted names.
        $key = 'file-upload:'.hash('sha256', Storage::getDefaultDriver().'|'.$directory);

        return Cache::lock($key, 120)->block(5, function () use ($directory, $filename, $contents): array {
            $name = $this->availableName($directory, $filename);
            $path = $directory.'/'.$name;
            $written = $contents instanceof UploadedFile
                ? $contents->storeAs($directory, $name)
                : Storage::put($path, $contents);

            if ($written === false) {
                throw new \RuntimeException('Failed to write file to storage');
            }

            return ['name' => $name, 'path' => $path, 'url' => self::publicUrl($path), 'renamed' => $name !== $filename];
        });
    }

    public static function publicUrl(string $path): string
    {
        return '/uploads/'.ltrim(preg_replace('#^public/#', '', $path) ?? $path, '/');
    }

    private function availableName(string $directory, string $filename): string
    {
        if (! Storage::exists($directory.'/'.$filename)) {
            return $filename;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $stem = pathinfo($filename, PATHINFO_FILENAME).'_'.now()->timestamp;
        $suffix = $extension !== '' ? '.'.$extension : '';
        $name = $stem.$suffix;
        $counter = 2;

        while (Storage::exists($directory.'/'.$name)) {
            $name = $stem.'_'.$counter++.$suffix;
        }

        return $name;
    }
}
