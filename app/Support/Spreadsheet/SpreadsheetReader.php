<?php

namespace App\Support\Spreadsheet;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class SpreadsheetReader
{
    /**
     * Read a single sheet of an uploaded spreadsheet as a Collection of associative arrays.
     *
     * When $headingRow is true, the first row's values are slug-cased (snake_case) and used
     * as keys for subsequent rows — matching the maatwebsite/excel WithHeadingRow behavior.
     */
    public static function readSheetAsCollection(UploadedFile $file, string $sheetName, bool $headingRow = true): Collection
    {
        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if ($sheet === null) {
            return collect();
        }

        $rows = $sheet->toArray(null, true, true, false);

        if (! $headingRow) {
            return collect($rows);
        }

        if ($rows === []) {
            return collect();
        }

        $headers = array_map(
            fn ($value) => Str::slug((string) $value, '_'),
            array_shift($rows)
        );

        return collect($rows)->map(function (array $row) use ($headers) {
            $combined = [];
            foreach ($headers as $index => $key) {
                $combined[$key] = $row[$index] ?? null;
            }

            return $combined;
        });
    }
}
