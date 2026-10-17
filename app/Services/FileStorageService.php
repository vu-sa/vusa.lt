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
     * @return array{files: list<array<string, mixed>>, directories: list<array{path: string, name: string, type: string, items: int}>, path: string}
     */
    public function listDirectory(string $path, ?array $extensions = null): array
    {
        $path = $this->normalizeFilePath($path);
        $directories = collect(Storage::directories($path))
            // `items` tells the browser whether a folder can be deleted (only empty ones can).
            ->map(fn (string $directory) => [
                'path' => $directory,
                'name' => basename($directory),
                'type' => 'directory',
                'items' => count(Storage::files($directory)) + count(Storage::directories($directory)),
            ])
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

    /**
     * Renames a file or folder in place. A file keeps its extension, so a rename cannot turn an
     * upload into a type StoreFilesRequest would have refused.
     *
     * @return array{status: 'renamed'|'unchanged'|'missing'|'taken'|'failed', name: string, path: string}
     */
    public function rename(string $path, string $name): array
    {
        $isDirectory = Storage::directoryExists($path);
        $name = $isDirectory ? $name : $this->keepExtension($path, $name);
        $target = dirname($path).'/'.$name;

        $status = match (true) {
            ! $isDirectory && ! Storage::fileExists($path) => 'missing',
            $target === $path => 'unchanged',
            // Case-only renames are allowed on case-insensitive disks, which report the target as existing.
            Storage::exists($target) && strcasecmp($target, $path) !== 0 => 'taken',
            ! Storage::move($path, $target) => 'failed',
            default => 'renamed',
        };

        return ['status' => $status, 'name' => $name, 'path' => $target];
    }

    /**
     * Moves files (never folders) into a directory, skipping any whose name is taken there.
     *
     * @param  list<string>  $sources
     * @return array{moved: list<string>, skipped: list<string>}
     */
    public function moveFiles(array $sources, string $destination): array
    {
        $moved = [];
        $skipped = [];

        foreach ($sources as $source) {
            $target = $destination.'/'.basename($source);
            $movable = Storage::fileExists($source) && dirname($source) !== $destination && ! Storage::exists($target);

            if ($movable && Storage::move($source, $target)) {
                $moved[] = $target;
            } else {
                $skipped[] = basename($source);
            }
        }

        return ['moved' => $moved, 'skipped' => $skipped];
    }

    private function keepExtension(string $path, string $name): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === '' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) === strtolower($extension)) {
            return $name;
        }

        return $name.'.'.$extension;
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
