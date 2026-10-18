<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Files\FilePathRequest;
use App\Http\Requests\Files\ScanFilesUsageRequest;
use App\Models\File;
use App\Services\FileStorageService;
use App\Services\FileUsageScanner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** Where files are referenced, checked before they are renamed or deleted. */
class FileUsageController extends AdminController
{
    public function __construct(protected FileStorageService $fileStorage) {}

    public function scanFileUsage(FilePathRequest $request, FileUsageScanner $scanner): RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path'));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        $directoryPath = dirname($path);
        if (! $request->user()->can('viewDirectory', [File::class, $directoryPath])) {
            throw new AuthorizationException(__('files.errors.no_scan_permission'));
        }

        if (! Storage::exists($path)) {
            return back()->withErrors(['error' => __('files.errors.file_not_found')]);
        }

        try {
            $usageData = $scanner->scanFileUsage($path);

            Log::info('File usage scanned', [
                'file_path' => $path,
                'total_usages' => $usageData['total_usages'],
                'is_safe_to_delete' => $usageData['is_safe_to_delete'],
                'user_id' => $request->user()->id,
            ]);

            if ($usageData['is_safe_to_delete']) {
                $message = __('files.messages.usage_safe', ['count' => count($usageData['scanned_models'])]);

                return back()->with('data', $usageData)->with('success', $message);
            } else {
                $message = __('files.messages.usage_found', ['count' => $usageData['total_usages']]);

                return back()->with('data', $usageData)->with('info', $message);
            }
        } catch (\Exception $e) {
            Log::error('File usage scan failed', [
                'file_path' => $path,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => __('files.errors.scan_failed', ['error' => $e->getMessage()])]);
        }
    }

    /** Usage of every file about to be deleted, flashed as `data.files`; files already gone are left out. */
    public function scanFilesUsage(ScanFilesUsageRequest $request, FileUsageScanner $scanner): RedirectResponse
    {
        $paths = [];

        foreach ($request->validated('paths') as $path) {
            try {
                $path = $this->fileStorage->normalizeFilePath($path);
            } catch (\InvalidArgumentException) {
                return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
            }

            if (! $request->user()->can('viewDirectory', [File::class, dirname($path)])) {
                throw new AuthorizationException(__('files.errors.no_scan_permission'));
            }

            $paths[] = $path;
        }

        $files = collect($paths)
            ->filter(fn (string $path): bool => Storage::fileExists($path))
            ->map(fn (string $path): array => ['path' => $path, ...$scanner->scanFileUsage($path)])
            ->values()
            ->all();

        return back()->with('data', ['files' => $files]);
    }
}
