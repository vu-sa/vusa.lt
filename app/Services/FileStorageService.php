<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\StagingProtection;
use App\Support\StoragePath;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileStorageService
{
    /** @var list<string> SVG cannot be rasterised; GIF re-encoding would lose animation. */
    private const array OPTIMISABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        protected ImageUploadService $imageUploadService,
        protected FileUploadWriter $writer,
    ) {}

    public function normalizeFilePath(string $path): string
    {
        if (! StoragePath::isSafeRelative($path) || StoragePath::hasTraversal($path)
            || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            throw new \InvalidArgumentException('Invalid path format');
        }

        $path = StoragePath::normalizeRelative($path);

        if ($path === 'public/files' || str_starts_with($path, 'public/files/')) {
            return $path;
        }

        if ($path === '' || $path === 'public' || str_starts_with($path, 'public/')) {
            throw new \InvalidArgumentException('Invalid path format');
        }

        return 'public/files/'.$path;
    }

    /**
     * @param  list<string>|null  $extensions
     * @return array{files: list<array<string, mixed>>, directories: list<array<string, string>>, path: string}
     */
    public function listDirectory(string $path, ?array $extensions = null): array
    {
        $path = $this->normalizeFilePath($path);
        $directories = collect(Storage::directories($path))
            ->map(fn (string $directory) => ['path' => $directory, 'name' => basename($directory), 'type' => 'directory'])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        $files = collect(Storage::files($path))
            ->filter(fn (string $file) => $extensions === null || in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true))
            ->map(fn (string $file) => [
                'path' => $file,
                'name' => basename($file),
                'type' => 'file',
                'size' => Storage::size($file),
                'modified' => Storage::lastModified($file),
                'mimeType' => Storage::mimeType($file),
                'url' => FileUploadWriter::publicUrl($file),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return ['files' => $files, 'directories' => $directories, 'path' => $path];
    }

    // Offered instead of a directory the user cannot view: their first readable tenant's folder.
    public function fallbackDirectory(User $user, Authorizer $authorizer): ?string
    {
        $tenant = $authorizer->tenants($user, 'files.read.padalinys')->first();

        if ($tenant === null) {
            return null;
        }

        $path = 'public/files/padaliniai/vusa'.$tenant->alias;

        return $user->can('viewDirectory', [File::class, $path]) ? $path : null;
    }

    // A content path is an editor marker; its destination comes from the actor's create scope.
    public static function isTipTapPath(string $path): bool
    {
        return str_starts_with($path, 'content/');
    }

    public function resolveTipTapDirectory(User $user, Authorizer $authorizer): string
    {
        $scope = $authorizer->scope($user, 'files.create.padalinys');

        if (! $scope->granted) {
            throw new AuthorizationException(__('files.errors.no_upload_permission'));
        }

        $tenant = $scope->isAllScope ? null : $scope->tenants->first();

        if (! $scope->isAllScope && $tenant === null) {
            throw new AuthorizationException(__('files.errors.no_upload_permission'));
        }

        if ($tenant === null || $tenant->isMain()) {
            return 'public/files/content/'.now()->format('Y/m');
        }

        return "public/files/padaliniai/vusa{$tenant->alias}/content/".now()->format('Y/m');
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return array{uploaded: list<array{name: string, path: string, url: string, renamed: bool}>, failed: list<array{name: string, reason: string}>}
     */
    public function storeMany(array $files, string $directory): array
    {
        StagingProtection::ensureFilesAreWritable();

        $uploaded = [];
        $failed = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();

            try {
                $uploaded[] = $this->storeOne($file, $directory);
            } catch (\Throwable $e) {
                $failed[] = [
                    'name' => $originalName,
                    'reason' => __($e instanceof LockTimeoutException ? 'files.errors.upload_busy' : 'files.errors.upload_failed'),
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
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, self::OPTIMISABLE_EXTENSIONS, true)) {
            $result = $this->imageUploadService->processAndSave($file, $directory, $originalName);

            return [
                'name' => $result['name'],
                'path' => $result['path'],
                'url' => $result['url'],
                'renamed' => $result['renamed'],
            ];
        }

        return $this->writer->write($directory, $originalName, $file);
    }
}
