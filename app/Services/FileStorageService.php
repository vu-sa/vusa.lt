<?php

namespace App\Services;

use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\StagingProtection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    /**
     * Raster formats worth re-encoding to WebP on upload. SVG and GIF are stored verbatim —
     * the driver cannot rasterise SVG, and re-encoding a GIF drops its animation.
     *
     * @var list<string>
     */
    private const array OPTIMISABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(protected ImageUploadService $imageUploadService) {}

    /**
     * TipTap posts a `content/Y/m` path. The real destination is derived from the user's tenant,
     * never from the request, so this prefix is a marker rather than a location.
     */
    public static function isTipTapPath(string $path): bool
    {
        return str_starts_with($path, 'content/');
    }

    /**
     * Where a TipTap upload actually goes, as a storage path including the `public/` prefix.
     */
    public function resolveTipTapDirectory(User $user, Authorizer $authorizer): string
    {
        $tenant = $user->isSuperAdmin() ? null : $authorizer->getTenants()->first();

        if ($tenant === null || $tenant->isMain()) {
            return 'public/files/content/'.date('Y/m');
        }

        return "public/files/padaliniai/vusa{$tenant->alias}/content/".date('Y/m');
    }

    /**
     * Store a batch of uploads into one already-authorized directory.
     *
     * A partial result is deliberate: one unreadable file in a ten-file drop should not cost
     * the caller the other nine.
     *
     * @param  list<UploadedFile>  $files
     * @param  string  $directory  Storage path including the `public/` disk prefix.
     * @return array{uploaded: list<array{name: string, path: string, url: string, renamed: bool}>, failed: list<array{name: string, reason: string}>}
     */
    public function storeMany(array $files, string $directory): array
    {
        $uploaded = [];
        $failed = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();

            try {
                $uploaded[] = $this->storeOne($file, $directory);
            } catch (\Throwable $e) {
                $failed[] = [
                    'name' => $originalName,
                    'reason' => $e->getMessage(),
                ];

                Log::error('File upload failed', [
                    'file_name' => $originalName,
                    'directory' => $directory,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['uploaded' => $uploaded, 'failed' => $failed];
    }

    /**
     * @return array{name: string, path: string, url: string, renamed: bool}
     */
    public function storeOne(UploadedFile $file, string $directory): array
    {
        StagingProtection::ensureFilesAreWritable();

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, self::OPTIMISABLE_EXTENSIONS, true)) {
            $result = $this->imageUploadService->processAndSave($file, $directory, $originalName);

            return [
                'name' => $result['name'],
                'path' => $result['path'],
                'url' => $result['url'],
                'renamed' => $result['name'] !== $originalName,
            ];
        }

        $name = $this->resolveAvailableName($directory, $originalName);

        if ($file->storeAs($directory, $name) === false) {
            throw new \RuntimeException('Failed to write file to storage');
        }

        $path = $directory.'/'.$name;

        return [
            'name' => $name,
            'path' => $path,
            'url' => static::publicUrl($path),
            'renamed' => $name !== $originalName,
        ];
    }

    /**
     * Storage paths carry the `public/` disk prefix; the browser reaches the same file through
     * the `public/uploads` symlink.
     */
    public static function publicUrl(string $path): string
    {
        return '/uploads/'.ltrim(preg_replace('#^public/#', '', $path) ?? $path, '/');
    }

    /**
     * Suffix a timestamp rather than overwrite when the name is taken.
     */
    private function resolveAvailableName(string $directory, string $originalName): string
    {
        if (! Storage::exists($directory.'/'.$originalName)) {
            return $originalName;
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        return $base.'_'.time().($extension !== '' ? '.'.$extension : '');
    }
}
