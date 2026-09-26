<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreFileableFilesRequest;
use App\Models\FileableFile;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Files a record keeps in SharePoint (meeting protocols, institution and type documents).
 * SharePoint is only storage here: files go in and come out through the app.
 */
class FileableFileController extends AdminController
{
    public function __construct(private readonly SharepointFileService $files) {}

    public function store(StoreFileableFilesRequest $request): RedirectResponse
    {
        $fileable = $request->fileable();

        /** @var array<int, array{file: UploadedFile, type: string, date: string, name?: string|null}> $entries */
        $entries = $request->validated('files');

        $failed = [];

        foreach ($entries as $index => $entry) {
            $upload = $entry['file'];
            $filename = $this->filenameFor($upload, $entry['name'] ?? null);

            try {
                $this->files->uploadFile($upload, $filename, $fileable, [
                    'Type' => $entry['type'],
                    'Date' => Carbon::parse($entry['date'])->format('Y-m-d'),
                ]);
            } catch (\Throwable $e) {
                Log::error('SharePoint upload failed', [
                    'fileable' => $fileable->getMorphClass().':'.$fileable->getKey(),
                    'name' => $filename,
                    'error' => $e->getMessage(),
                ]);
                $failed[$index] = $filename;
            }
        }

        $total = count($entries);
        $uploaded = $total - count($failed);

        if ($failed !== []) {
            $message = $uploaded === 0
                ? __('messages.sharepoint.upload_failed', ['failed' => implode(', ', $failed)])
                : __('messages.sharepoint.uploaded_partially', [
                    'uploaded' => $uploaded,
                    'total' => $total,
                    'failed' => implode(', ', $failed),
                ]);

            // The sheet keeps just these rows open, so a retry never re-uploads the ones that landed.
            return back()->with('error', $message)->with('data', ['failed_file_indexes' => array_keys($failed)]);
        }

        return back()->with('success', __('messages.sharepoint.uploaded_many', ['count' => $uploaded]));
    }

    /**
     * Opening a file is one click: find or mint the anonymous link, then go there.
     */
    public function open(FileableFile $fileableFile): RedirectResponse
    {
        $this->authorize('view', $fileableFile);

        try {
            return redirect()->away($this->files->publicLinkFor($fileableFile));
        } catch (\Throwable $e) {
            return back()->with('error', $this->linkFailureMessage($fileableFile, $e));
        }
    }

    public function publicLink(FileableFile $fileableFile): JsonResponse
    {
        $this->authorize('view', $fileableFile);

        try {
            return response()->json(['url' => $this->files->publicLinkFor($fileableFile)]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $this->linkFailureMessage($fileableFile, $e)], 422);
        }
    }

    public function destroy(FileableFile $fileableFile): RedirectResponse
    {
        $this->authorize('delete', $fileableFile);

        try {
            $this->files->deleteFile($fileableFile);
        } catch (\Throwable $e) {
            if (! SharepointFileService::isNotFound($e)) {
                $fileableFile->markAsDeletedExternally();

                return back()->with('info', __('messages.sharepoint.deleted_locally_only'));
            }
        }

        $fileableFile->delete();

        return back()->with('info', __('messages.sharepoint.file_deleted'));
    }

    private function filenameFor(UploadedFile $upload, ?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return $upload->getClientOriginalName();
        }

        return $name.'.'.strtolower($upload->getClientOriginalExtension());
    }

    private function linkFailureMessage(FileableFile $file, \Throwable $e): string
    {
        if (SharepointFileService::isNotFound($e)) {
            $file->markAsDeletedExternally();

            return __('messages.sharepoint.file_missing');
        }

        Log::warning('Could not resolve a public link for a fileable file', [
            'file_id' => $file->id,
            'error' => $e->getMessage(),
        ]);

        return __('messages.sharepoint.link_failed');
    }
}
