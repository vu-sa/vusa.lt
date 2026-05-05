<?php

namespace App\Support\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetWriter
{
    /**
     * Stream a 2D array of rows as an .xlsx download.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    public static function downloadXlsx(array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $writer = new Xlsx($spreadsheet);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="%s"', addcslashes($filename, '"\\')),
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ];

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, $headers);
    }
}
