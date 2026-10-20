<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Files\MoveFilesRequest;
use App\Http\Requests\Files\RenameFileRequest;
use App\Models\File;
use App\Services\FileStorageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** Renaming and moving in the file manager; references in content are not rewritten. */
class FileOrganizationController extends AdminController
{
    public function __construct(protected FileStorageService $fileStorage) {}

    public function rename(RenameFileRequest $request): RedirectResponse
    {
        try {
            $path = $this->fileStorage->normalizeFilePath($request->validated('path'));
        } catch (\InvalidArgumentException) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        if ($path === 'public/files') {
            return back()->withErrors(['error' => __('files.errors.cannot_rename_root')]);
        }

        if (! $request->user()->can('updateInDirectory', [File::class, dirname($path)])) {
            throw new AuthorizationException(__('files.errors.no_rename_permission'));
        }

        ['status' => $status, 'name' => $name, 'path' => $target] = $this->fileStorage->rename($path, trim((string) $request->validated('name')));

        if ($status === 'renamed') {
            Log::info('File renamed', ['from' => $path, 'to' => $target, 'user_id' => $request->user()->id]);
        }

        return match ($status) {
            'renamed' => back()->with('success', __('files.messages.renamed', ['name' => $name])),
            'unchanged' => back(),
            'missing' => back()->withErrors(['error' => __('files.errors.file_not_found')]),
            'taken' => back()->withErrors(['name' => __('files.errors.name_taken')]),
            default => back()->withErrors(['error' => __('files.errors.rename_failed')]),
        };
    }

    /** Every source folder and the destination are authorized before anything moves. */
    public function move(MoveFilesRequest $request): RedirectResponse
    {
        try {
            $destination = $this->fileStorage->normalizeFilePath($request->validated('destination'));
            $sources = array_map($this->fileStorage->normalizeFilePath(...), $request->validated('paths'));
        } catch (\InvalidArgumentException) {
            return back()->withErrors(['error' => __('files.errors.invalid_file_path')]);
        }

        $user = $request->user();
        $permitted = $user->can('createInDirectory', [File::class, $destination])
            && collect($sources)->every(fn (string $source) => $user->can('updateInDirectory', [File::class, dirname($source)]));

        if (! $permitted) {
            throw new AuthorizationException(__('files.errors.no_move_permission'));
        }

        if (! Storage::directoryExists($destination)) {
            return back()->withErrors(['destination' => __('files.errors.directory_not_found')]);
        }

        ['moved' => $moved, 'skipped' => $skipped] = $this->fileStorage->moveFiles($sources, $destination);

        Log::info('Files moved', ['to' => $destination, 'moved' => $moved, 'skipped' => $skipped, 'user_id' => $user->id]);

        $skippedList = implode(', ', array_slice($skipped, 0, 3));

        if ($moved === []) {
            return back()->withErrors(['error' => __('files.errors.move_all_failed', ['files' => $skippedList])]);
        }

        $message = trans_choice('files.messages.moved_count', count($moved), ['count' => count($moved)]);

        return $skipped === []
            ? back()->with('success', $message)
            : back()->with('warning', $message.' '.__('files.messages.move_skipped', ['files' => $skippedList]));
    }
}
