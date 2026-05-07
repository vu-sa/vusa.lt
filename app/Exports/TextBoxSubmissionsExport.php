<?php

namespace App\Exports;

use App\Models\ContentPart;
use App\Models\TextBoxSubmission;
use App\Support\Spreadsheet\SpreadsheetWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TextBoxSubmissionsExport
{
    public function __construct(public ContentPart $contentPart) {}

    public function download(string $filename): StreamedResponse
    {
        return SpreadsheetWriter::downloadXlsx($this->rows(), $filename);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function rows(): array
    {
        $headings = ['Response', 'Submitted by', 'Submitted at'];

        $data = $this->contentPart
            ->textBoxSubmissions()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TextBoxSubmission $submission) => [/** @phpstan-ignore argument.type */
                $submission->text,
                $submission->user?->name ?? 'Anonymous', /** @phpstan-ignore nullsafe.neverNull */
                $submission->created_at->toDateTimeString(),
            ])
            ->toArray();

        return array_merge([$headings], $data);
    }
}
