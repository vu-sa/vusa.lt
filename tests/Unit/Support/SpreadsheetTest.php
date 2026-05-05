<?php

use App\Support\Spreadsheet\SpreadsheetReader;
use App\Support\Spreadsheet\SpreadsheetWriter;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

describe('SpreadsheetWriter', function () {
    test('downloadXlsx streams an xlsx file with the expected headers and content', function () {
        $rows = [
            ['ID', 'Name', 'Email'],
            [1, 'Alice', 'alice@example.com'],
            [2, 'Bob', 'bob@example.com'],
        ];

        $response = SpreadsheetWriter::downloadXlsx($rows, 'people.xlsx');

        expect($response->headers->get('Content-Type'))
            ->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        expect($response->headers->get('Content-Disposition'))
            ->toContain('attachment')
            ->toContain('people.xlsx');

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_').'.xlsx';
        file_put_contents($tmp, $body);

        $loaded = IOFactory::load($tmp);
        expect($loaded->getActiveSheet()->toArray(null, true, true, false))->toEqual([
            ['ID', 'Name', 'Email'],
            [1, 'Alice', 'alice@example.com'],
            [2, 'Bob', 'bob@example.com'],
        ]);

        unlink($tmp);
    });
});

describe('SpreadsheetReader', function () {
    test('readSheetAsCollection keys rows by snake_cased headings', function () {
        $tmp = createXlsxFixture('IMPORT', [
            ['Vardas ir pavardė', 'Studentinis el. paštas', 'Tel. nr.', 'Narystės pradžia', 'Narystės pabaiga'],
            ['Jonas Jonaitis', 'jonas@vu.lt', 'tel-here', 45000, 45365],
        ]);

        $upload = new UploadedFile($tmp, 'import.xlsx', null, null, true);

        $rows = SpreadsheetReader::readSheetAsCollection($upload, 'IMPORT');

        expect($rows)->toHaveCount(1);
        expect($rows->first())->toMatchArray([
            'vardas_ir_pavarde' => 'Jonas Jonaitis',
            'studentinis_el_pastas' => 'jonas@vu.lt',
            'tel_nr' => 'tel-here',
            'narystes_pradzia' => 45000,
            'narystes_pabaiga' => 45365,
        ]);

        unlink($tmp);
    });

    test('readSheetAsCollection returns empty collection when sheet is missing', function () {
        $tmp = createXlsxFixture('OTHER', [['a', 'b'], [1, 2]]);
        $upload = new UploadedFile($tmp, 'import.xlsx', null, null, true);

        expect(SpreadsheetReader::readSheetAsCollection($upload, 'IMPORT'))->toBeEmpty();

        unlink($tmp);
    });
});

function createXlsxFixture(string $sheetName, array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetName);
    $sheet->fromArray($rows, null, 'A1');

    $tmp = tempnam(sys_get_temp_dir(), 'fixture_').'.xlsx';
    (new XlsxWriter($spreadsheet))->save($tmp);

    return $tmp;
}
