<?php

use App\Models\Banner;
use App\Models\Content;
use App\Models\ContentEditorDraft;
use App\Models\ContentPart;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\TenantHomepageContent;
use App\Models\User;
use App\Services\FileUsageScanner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

pest()->use(RefreshDatabase::class);

/**
 * The scanner drives the "Saugu trinti" badge admins act on by deleting the
 * file. Every miss here is a broken image or link on the live site, so
 * fixtures are written through the real models: what is matched is exactly
 * what production stores.
 */
beforeEach(function (): void {
    Storage::fake();
    $this->scanner = app(FileUsageScanner::class);
    $this->relativePath = 'padaliniai/vusamif/Renginių nuotraukos/Ąžuolų šventė 2024 (1).jpg';
    $this->url = '/uploads/files/'.$this->relativePath;
});

function scanUsage(string $relativePath): array
{
    return test()->scanner->scanFileUsage('public/files/'.$relativePath);
}

function makeTiptapImagePart(string $src, array $attributes = []): ContentPart
{
    return ContentPart::factory()->create([
        'content_id' => Content::factory()->create()->id,
        'json_content' => [
            'type' => 'doc',
            'content' => [
                ['type' => 'image', 'attrs' => ['src' => $src, 'alt' => 'fixture']],
            ],
        ],
        ...$attributes,
    ]);
}

/**
 * A value for the column in the shape its editor writes: HTML for rich-text
 * fields, a JSON object for array casts, the bare URL otherwise.
 */
function referencingValue(Model $model, string $column, string $url): mixed
{
    $isRichText = in_array($column, ['short', 'description', 'solution', 'steps_taken', 'evaluation'], true);
    $value = $isRichText ? '<p>Žr. <a href="'.$url.'">failą</a></p>' : $url;

    if (method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($column)) {
        return ['lt' => $value, 'en' => $value];
    }

    if ($model->hasCast($column, ['array', 'json', 'object'])) {
        return ['image' => $value];
    }

    return $value;
}

describe('coverage of every registered column', function (): void {
    test('every registered column exists', function (): void {
        foreach (FileUsageScanner::targets() as [$modelClass, $columns]) {
            $table = (new $modelClass)->getTable();

            foreach ($columns as $column) {
                expect(Schema::hasColumn($table, $column))->toBeTrue("{$table}.{$column} is not a column");
            }
        }
    });

    test('a reference in any registered column is found', function (string $key, string $column): void {
        [$modelClass] = FileUsageScanner::targets()[$key];
        $value = referencingValue(new $modelClass, $column, $this->url);

        match ($modelClass) {
            ContentPart::class => $column === 'options'
                ? makeTiptapImagePart('/elsewhere.jpg', ['options' => ['backgroundImage' => $this->url]])
                : makeTiptapImagePart($this->url),
            ContentEditorDraft::class => ContentEditorDraft::query()->create([
                'user_id' => User::factory()->create()->id,
                'kind' => 'page',
                'identity' => 'new',
                'snapshot' => ['featured_image' => $this->url],
                'revision' => 1,
            ]),
            default => $modelClass::factory()->create([$column => $value]),
        };

        $result = scanUsage($this->relativePath);

        expect($result['is_safe_to_delete'])->toBeFalse()
            ->and($result['total_usages'])->toBe(1)
            ->and($result['usage_details'][0]['model_type'])->toBe($key === 'contentParts' ? 'content' : $key);
    })->with(function (): Generator {
        foreach (FileUsageScanner::targets() as $key => [, $columns]) {
            foreach ($columns as $column) {
                yield "{$key}.{$column}" => [$key, $column];
            }
        }
    });
});

describe('encodings production writes', function (): void {
    test('a link to a file with spaces in sanitized article HTML is found', function (): void {
        $news = News::factory()->create(['short' => '<p><a href="'.$this->url.'">Ataskaita</a></p>']);

        // The sanitizer percent-encodes the space, which the old scanner never searched for.
        expect($news->getRawOriginal('short'))->toContain('%20');

        $result = scanUsage($this->relativePath);

        expect($result['total_usages'])->toBe(1)
            ->and($result['usage_details'][0]['model_type'])->toBe('news');
    });

    test('a block of any media-holding type is found', function (string $type, Closure $jsonContent): void {
        $news = News::factory()->create();
        ContentPart::factory()->create([
            'content_id' => $news->content_id,
            'type' => $type,
            'json_content' => $jsonContent($this->url),
        ]);

        $result = scanUsage($this->relativePath);

        expect($result['total_usages'])->toBe(1)
            ->and($result['usage_details'][0])->toMatchArray(['model_type' => 'news', 'id' => $news->id]);
    })->with([
        'hero' => ['hero', fn (string $url): array => ['title' => 'Sveiki', 'imageSrc' => $url]],
        'image-grid' => ['image-grid', fn (string $url): array => [['image' => $url, 'colspan' => 'col-span-1']]],
        'photo-gallery' => ['photo-gallery', fn (string $url): array => [['src' => $url, 'alt' => 'Nuotrauka']]],
        'content-grid' => ['content-grid', fn (string $url): array => [['columns' => [['width' => 'col-span-6', 'content' => ['type' => 'image', 'value' => $url]]]]]],
        'hero-carousel' => ['hero-carousel', fn (string $url): array => [['title' => 'Skaidrė', 'imageSrc' => $url]]],
        'carousel-slide-deck' => ['carousel-slide-deck', fn (string $url): array => [['title' => 'Skaidrė', 'imageSrc' => $url]]],
        'spotify-embed' => ['spotify-embed', fn (string $url): array => ['url' => 'https://open.spotify.com/show/x', 'panelImage' => $url]],
        'link-list' => ['link-list', fn (string $url): array => ['links' => [['title' => 'Failas', 'url' => 'https://www.vusa.lt'.$url]]]],
        'person-quote' => ['person-quote', fn (string $url): array => ['snapshot' => ['name' => 'Jonas', 'photoUrl' => $url]]],
    ]);

    test('an absolute static.vusa.lt URL in a tiptap body is found', function (): void {
        $part = makeTiptapImagePart('https://static.vusa.lt/uploads/2018-2019/foto.jpg');
        News::factory()->create(['content_id' => $part->content_id]);

        $result = scanUsage('2018-2019/foto.jpg');

        expect($result['total_usages'])->toBe(1)
            ->and($result['usage_details'][0]['model_type'])->toBe('news');
    });

    test('an absolute www.vusa.lt URL in a plain column is found', function (): void {
        Banner::factory()->create(['image_url' => 'https://www.vusa.lt'.$this->url]);

        expect(scanUsage($this->relativePath)['total_usages'])->toBe(1);
    });
});

describe('references to other files', function (): void {
    test('the same path on a foreign domain is not a usage', function (): void {
        makeTiptapImagePart('https://cdn.example.com'.$this->url);

        expect(scanUsage($this->relativePath)['is_safe_to_delete'])->toBeTrue();
    });

    test('a file with the same name in another folder is not a usage', function (): void {
        Banner::factory()->create(['image_url' => '/uploads/files/kitas aplankas/Ąžuolų šventė 2024 (1).jpg']);

        expect(scanUsage($this->relativePath)['is_safe_to_delete'])->toBeTrue();
    });

    test('LIKE wildcards in a filename stay literal', function (): void {
        Banner::factory()->create(['image_url' => '/uploads/files/50 nuolaidaxstudentams.pdf']);

        expect(scanUsage('50% nuolaida_studentams.pdf')['is_safe_to_delete'])->toBeTrue();
    });

    test('the legacy path counts only while no other file lives there', function (): void {
        Banner::factory()->create(['image_url' => '/uploads/news/foto.webp']);

        expect(scanUsage('news/foto.webp')['is_safe_to_delete'])->toBeFalse();

        Storage::put('public/news/foto.webp', 'shared-folder image');

        expect(scanUsage('news/foto.webp')['is_safe_to_delete'])->toBeTrue();
    });
});

describe('owners and freshness', function (): void {
    test('a block on a trashed page is reported as that page', function (): void {
        $page = Page::factory()->create(['title' => 'Archyvuotas puslapis']);
        makeTiptapImagePart($this->url, ['content_id' => $page->content_id]);
        $page->delete();

        $detail = scanUsage($this->relativePath)['usage_details'][0];

        expect($detail)->toMatchArray([
            'model_type' => 'page',
            'id' => $page->id,
            'title' => 'Archyvuotas puslapis',
            'url' => route('pages.edit', $page->id),
            'matched_parts_count' => 1,
        ]);
    });

    test('a block on a tenant homepage is reported as that tenant', function (): void {
        $tenant = Tenant::query()->first();
        $part = makeTiptapImagePart($this->url);
        TenantHomepageContent::query()->forceCreate([
            'tenant_id' => $tenant->id,
            'content_id' => $part->content_id,
            'locale' => 'lt',
        ]);

        expect(scanUsage($this->relativePath)['usage_details'][0])
            ->toMatchArray(['model_type' => 'tenant', 'id' => $tenant->id]);
    });

    test('a reference added after a scan is found by the next scan', function (): void {
        expect(scanUsage($this->relativePath)['is_safe_to_delete'])->toBeTrue();

        Banner::factory()->create(['image_url' => $this->url]);

        expect(scanUsage($this->relativePath)['is_safe_to_delete'])->toBeFalse();
    });
});
