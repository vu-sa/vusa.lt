<?php

use App\Actions\Media\AddImageMedia;
use App\Models\Banner;
use App\Models\News;
use App\Models\Tenant;
use App\Services\Media\LegacyImageResolver;
use App\Support\Media\ImageConversions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

function dropLegacyImageColumnsMigration(): Migration
{
    return require base_path('database/migrations/2026_10_19_192000_drop_legacy_image_columns.php');
}

beforeEach(function (): void {
    dropLegacyImageColumnsMigration()->down();
    Storage::fake('spatieMediaLibrary');
});

test('resolvable legacy images block every column drop, including images on trashed records', function (bool $trashed): void {
    $uploads = sys_get_temp_dir().'/legacy-drop-'.Str::random(8);
    File::ensureDirectoryExists($uploads.'/news');
    File::put($uploads.'/news/original.jpg', 'original');
    app()->instance(LegacyImageResolver::class, new LegacyImageResolver($uploads, $uploads));
    $news = News::factory()->for(Tenant::query()->first())->create(['image' => '/uploads/news/original.jpg']);
    if ($trashed) {
        $news->delete();
    }

    try {
        expect(fn () => dropLegacyImageColumnsMigration()->up())
            ->toThrow(RuntimeException::class, 'media migration or cleanup is pending');

        foreach (['news' => ['image', 'image_author'], 'banners' => ['image_url'], 'institutions' => ['image_url', 'image_focal_point'], 'pages' => ['featured_image']] as $table => $columns) {
            expect(Schema::hasColumns($table, $columns))->toBeTrue();
        }
        expect(File::get($uploads.'/news/original.jpg'))->toBe('original');
    } finally {
        File::deleteDirectory($uploads);
    }
})->with(['live' => false, 'trashed' => true]);

test('unfinished media upgrades block the schema change', function (): void {
    $news = News::factory()->for(Tenant::query()->first())->create();
    $media = app(AddImageMedia::class)->execute($news, UploadedFile::fake()->image('cover.jpg'), 'image');
    $media->forgetCustomProperty('image_policy')->save();

    expect(fn () => dropLegacyImageColumnsMigration()->up())->toThrow(RuntimeException::class)
        ->and(Schema::hasColumn('news', 'image'))->toBeTrue();
    Storage::disk('spatieMediaLibrary')->assertExists($media->getPathRelativeToRoot());
});

test('superseded media files must be cleaned up before the schema change', function (): void {
    $news = News::factory()->for(Tenant::query()->first())->create();
    $media = app(AddImageMedia::class)->execute($news, UploadedFile::fake()->image('cover.jpg'), 'image');
    $media->generated_conversions = array_fill_keys(array_keys(ImageConversions::WIDTHS), true);
    $media->setCustomProperty('superseded_file', 'old.jpg')->save();

    expect(fn () => dropLegacyImageColumnsMigration()->up())->toThrow(RuntimeException::class)
        ->and(Schema::hasColumn('news', 'image'))->toBeTrue();
});

test('completed migration drops retired columns and retains avatar and logo caches', function (): void {
    $news = News::factory()->for(Tenant::query()->first())->create(['image' => '/uploads/news/missing-image.jpg']);

    dropLegacyImageColumnsMigration()->up();

    foreach (['news' => ['image', 'image_author'], 'banners' => ['image_url'], 'institutions' => ['image_url', 'image_focal_point'], 'pages' => ['featured_image']] as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::hasColumn($table, $column))->toBeFalse();
        }
    }
    expect(Schema::hasColumn('institutions', 'logo_url'))->toBeTrue()
        ->and(Schema::hasColumns('users', ['profile_photo_path', 'profile_photo_focal_point']))->toBeTrue()
        ->and(Schema::hasColumns('dutiables', ['additional_photo', 'additional_photo_focal_point']))->toBeTrue();
    $this->assertModelExists($news);
});

test('rollback restores text banner URLs and the news author default', function (): void {
    $url = 'https://www.vusa.lt/'.str_repeat('long-path/', 30).'image.jpg';
    $banner = Banner::factory()->create(['image_url' => $url]);
    $news = News::factory()->for(Tenant::query()->first())->create();

    expect(Schema::getColumnType('banners', 'image_url'))->toBe('text')
        ->and($banner->fresh()->image_url)->toBe($url)
        ->and($news->fresh()->image_author)->toBe('VU SA archyvo nuotrauka');

    if (Schema::getConnection()->getDriverName() === 'mysql') {
        expect(Schema::getColumnType('news', 'image', true))->toBe('varchar(200)')
            ->and(Schema::getColumnType('news', 'image_author', true))->toBe('varchar(200)')
            ->and(Schema::getColumnType('institutions', 'image_url', true))->toBe('varchar(255)')
            ->and(Schema::getColumnType('institutions', 'image_focal_point', true))->toBe('varchar(20)')
            ->and(Schema::getColumnType('pages', 'featured_image', true))->toBe('varchar(255)');
    }
});
