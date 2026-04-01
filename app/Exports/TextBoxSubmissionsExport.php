<?php

namespace App\Exports;

use App\Models\ContentPart;
use App\Models\TextBoxSubmission;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TextBoxSubmissionsExport implements FromArray, WithHeadings
{
    public function __construct(public ContentPart $contentPart) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Response', 'Submitted by', 'Submitted at'];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return $this->contentPart
            ->textBoxSubmissions()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TextBoxSubmission $submission) => [/** @phpstan-ignore argument.type */
                $submission->text,
                $submission->user->name ?? 'Anonymous',
                $submission->created_at->toDateTimeString(),
            ])
            ->toArray();
    }
}
