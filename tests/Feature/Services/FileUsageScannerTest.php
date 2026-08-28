<?php

use App\Models\Banner;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\News;
use App\Services\FileUsageScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

/**
 * Content often stores fully-qualified vusa.lt URLs (https://static.vusa.lt/…)
 * instead of site-relative paths, and serialized JSON escapes '/' as '\/'.
 * Both facts used to hide real usages, marking referenced files as safe to
 * delete. Only vusa.lt hosts count: an absolute URL on a foreign domain is a
 * different copy of the file.
 */
beforeEach(function (): void {
    Cache::flush();
    $this->scanner = app(FileUsageScanner::class);
    $this->fileName = 'scanner-absolute-url-fixture.jpg';
});

function makeTiptapImagePart(string $src): ContentPart
{
    $content = Content::factory()->create();

    return ContentPart::factory()->create([
        'content_id' => $content->id,
        'json_content' => [
            'type' => 'doc',
            'content' => [
                ['type' => 'image', 'attrs' => ['src' => $src, 'alt' => 'fixture']],
            ],
        ],
    ]);
}

describe('absolute vusa.lt URLs', function (): void {
    test('a static.vusa.lt URL in a tiptap body is detected', function (): void {
        $part = makeTiptapImagePart('https://static.vusa.lt/uploads/2018-2019/'.$this->fileName);
        News::factory()->create(['content_id' => $part->content_id]);

        $result = $this->scanner->scanFileUsage('public/files/2018-2019/'.$this->fileName);

        expect($result['is_safe_to_delete'])->toBeFalse()
            ->and($result['total_usages'])->toBe(1)
            ->and($result['usage_details'][0]['model_type'])->toBe('news');
    });

    test('a www.vusa.lt URL in a plain text field is detected', function (): void {
        Banner::factory()->create(['image_url' => 'https://www.vusa.lt/uploads/files/'.$this->fileName]);

        $result = $this->scanner->scanFileUsage('public/files/'.$this->fileName);

        expect($result['is_safe_to_delete'])->toBeFalse()
            ->and($result['total_usages'])->toBe(1);
    });
});

describe('relative references', function (): void {
    test('a site-relative path in a tiptap body is detected', function (): void {
        makeTiptapImagePart('/uploads/2018-2019/'.$this->fileName);

        $result = $this->scanner->scanFileUsage('public/files/2018-2019/'.$this->fileName);

        expect($result['total_usages'])->toBe(1);
    });
});

describe('foreign domains', function (): void {
    test('the same path on a foreign domain is not counted as usage', function (): void {
        makeTiptapImagePart('https://cdn.example.com/uploads/2018-2019/'.$this->fileName);

        $result = $this->scanner->scanFileUsage('public/files/2018-2019/'.$this->fileName);

        expect($result['is_safe_to_delete'])->toBeTrue()
            ->and($result['total_usages'])->toBe(0);
    });
});
