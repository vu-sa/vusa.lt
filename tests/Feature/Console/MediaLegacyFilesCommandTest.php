<?php

use App\Exceptions\StagingResourceReadOnlyException;
use App\Models\News;
use App\Models\Tenant;
use App\Services\FileUsageScanner;
use App\Services\Media\LegacyFileReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->uploads = sys_get_temp_dir().'/legacy-files-'.Str::random(8);
    File::ensureDirectoryExists($this->uploads.'/contacts');
    File::put($this->uploads.'/contacts/forgotten.webp', str_repeat('x', 2048));
    File::put($this->uploads.'/contacts/still-linked.webp', 'x');
    app()->instance(LegacyFileReport::class, new LegacyFileReport(app(FileUsageScanner::class), $this->uploads));

    News::factory()->for(Tenant::query()->first())->create([
        'short' => '<p><img src="/uploads/contacts/still-linked.webp"></p>',
    ]);
});

afterEach(function (): void {
    File::deleteDirectory($this->uploads);
});

test('the report lists only legacy files nothing references', function (): void {
    $this->artisan('media:legacy-files', ['--group' => 'contacts', '--list' => true])
        ->expectsOutputToContain('contacts/forgotten.webp')
        ->doesntExpectOutputToContain('contacts/still-linked.webp')
        ->assertSuccessful();
});

test('verbose output shows each file and its progress within the requested folder', function (string $flag): void {
    $this->artisan('media:legacy-files', ['--group' => 'contacts', $flag => true])
        ->expectsOutputToContain('Scanning contacts...')
        ->expectsOutputToContain('contacts [1/2] contacts/forgotten.webp: unreferenced')
        ->expectsOutputToContain('contacts [2/2] contacts/still-linked.webp: referenced')
        ->doesntExpectOutputToContain('Scanning news...')
        ->assertSuccessful();
})->with(['-v', '--verbose']);

test('quiet suppresses progress even with verbose enabled', function (): void {
    $this->artisan('media:legacy-files', ['--group' => 'contacts', '--verbose' => true, '--quiet' => true])
        ->doesntExpectOutputToContain('Scanning contacts')
        ->doesntExpectOutputToContain('forgotten.webp')
        ->assertSuccessful();
});

test('verbose output reports an empty folder without scanning other groups', function (): void {
    $this->artisan('media:legacy-files', ['--group' => 'pages', '--verbose' => true])
        ->expectsOutputToContain('Scanning pages...')
        ->doesntExpectOutputToContain('Scanning contacts')
        ->assertSuccessful();
});

test('file progress is reported incrementally without changing the report', function (): void {
    $events = [];
    $report = app(LegacyFileReport::class);
    $result = $report->build('contacts', function ($group, $checked, $total, $path, $referenced) use (&$events): void {
        $events[] = [$checked, $total, $path, $referenced];
    });
    expect($result)->toBe($report->build('contacts'))
        ->and($events)->toBe([[0, 0, null, null], [1, 2, 'contacts/forgotten.webp', false], [2, 2, 'contacts/still-linked.webp', true]]);
});

test('nothing is deleted while the legacy columns could still hide a reference', function (): void {
    $this->artisan('media:legacy-files', ['--delete' => true, '--force' => true])
        ->expectsOutputToContain('news.image still exists')
        ->assertFailed();

    expect(File::exists($this->uploads.'/contacts/forgotten.webp'))->toBeTrue();
});

test('nothing is deleted on staging, whose uploads are production\'s', function (): void {
    config(['app.env' => 'staging', 'app.files_read_only' => true]);

    expect(fn () => $this->artisan('media:legacy-files', ['--delete' => true, '--force' => true])->run())
        ->toThrow(StagingResourceReadOnlyException::class);

    expect(File::exists($this->uploads.'/contacts/forgotten.webp'))->toBeTrue();
});

test('once the columns are gone only unreferenced files are deleted', function (): void {
    foreach (LegacyFileReport::LEGACY_COLUMNS as $table => $column) {
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
    }

    $this->artisan('media:legacy-files', ['--group' => 'contacts', '--delete' => true, '--force' => true])->assertSuccessful();

    expect(File::exists($this->uploads.'/contacts/forgotten.webp'))->toBeFalse()
        ->and(File::exists($this->uploads.'/contacts/still-linked.webp'))->toBeTrue();
});
