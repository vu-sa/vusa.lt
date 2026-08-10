<?php

use App\Models\Calendar;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Form;
use App\Models\FormField;
use App\Models\News;
use App\Models\Tenant;
use App\Models\Training;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Every field covered here is Tiptap-authored HTML that a public page renders
 * with `v-html`, so anything stored unsanitized executes in a visitor's browser.
 * The write-path guarantee is what these assert: sanitizing at render would miss
 * seeders, factories and DuplicateNewsAction.
 */
beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
});

test('calendar description is sanitized on write', function (): void {
    $calendar = Calendar::factory()->create([
        'tenant_id' => $this->tenant->id,
        'description' => ['lt' => '<p>Renginys</p><script>alert(1)</script>'],
    ]);

    expect($calendar->fresh()->getTranslation('description', 'lt'))
        ->toContain('Renginys')
        ->not->toContain('<script');

    $calendar->update(['description' => ['lt' => '<img src=x onerror="alert(1)">']]);

    expect($calendar->fresh()->getTranslation('description', 'lt'))->not->toContain('onerror');
});

test('form description is sanitized on write', function (): void {
    $form = Form::factory()->create([
        'tenant_id' => $this->tenant->id,
        'description' => ['lt' => '<p>Registracija</p><script>alert(1)</script>'],
    ]);

    expect($form->fresh()->getTranslation('description', 'lt'))
        ->toContain('Registracija')
        ->not->toContain('<script');
});

test('form field description is sanitized on write', function (): void {
    $field = FormField::factory()->create([
        'description' => ['lt' => '<p>Paaiškinimas</p><a href="javascript:alert(1)">x</a>'],
    ]);

    expect($field->fresh()->getTranslation('description', 'lt'))
        ->toContain('Paaiškinimas')
        ->not->toContain('javascript:');
});

test('training description is sanitized on write', function (): void {
    $training = Training::factory()->create([
        'description' => ['lt' => '<p>Mokymai</p><img src=x onerror="alert(1)">'],
    ]);

    expect($training->fresh()->getTranslation('description', 'lt'))
        ->toContain('Mokymai')
        ->not->toContain('onerror');
});

/**
 * News is not translatable — locales are separate rows — so `short` goes through
 * a plain Attribute mutator rather than the setTranslation() hook.
 */
test('news short is sanitized on write', function (): void {
    $news = News::factory()->create([
        'tenant_id' => $this->tenant->id,
        'short' => '<p>Santrauka</p><script>alert(1)</script>',
    ]);

    expect($news->fresh()->short)
        ->toContain('Santrauka')
        ->not->toContain('<script');

    $news->update(['short' => '<img src=x onerror="alert(1)">']);

    expect($news->fresh()->short)->not->toContain('onerror');
});

test('news short keeps the formatting its editor produces', function (): void {
    $news = News::factory()->create([
        'tenant_id' => $this->tenant->id,
        'short' => '<p><strong>Svarbu</strong> ir <em>įdomu</em></p>',
    ]);

    expect($news->fresh()->short)
        ->toContain('<strong>Svarbu</strong>')
        ->toContain('<em>įdomu</em>');
});

describe('content part json_content', function (): void {
    beforeEach(function (): void {
        $this->content = Content::factory()->create();
    });

    /**
     * Accordion items carry editor-produced HTML inside json_content, which
     * ContentService persists verbatim from the request. RCAccordion.vue renders
     * `item.html` with `v-html`.
     */
    test('nested html strings are sanitized on write', function (): void {
        $part = ContentPart::factory()->create([
            'content_id' => $this->content->id,
            'type' => 'shadcn-accordion',
            'json_content' => [
                ['title' => 'Klausimas', 'html' => '<p>Atsakymas</p><script>alert(1)</script>'],
                ['title' => 'Antras', 'html' => '<img src=x onerror="alert(1)">'],
            ],
        ]);

        $stored = $part->fresh()->json_content->toArray();

        expect($stored[0]['html'])->toContain('Atsakymas')->not->toContain('<script')
            ->and($stored[1]['html'])->not->toContain('onerror')
            // `title` on a non-hero block is plain text and must survive untouched.
            ->and($stored[0]['title'])->toBe('Klausimas');
    });

    test('hero title is sanitized while other blocks keep plain-text titles', function (): void {
        $hero = ContentPart::factory()->create([
            'content_id' => $this->content->id,
            'type' => 'hero',
            'json_content' => ['title' => '<strong>Sveiki</strong><script>alert(1)</script>'],
        ]);

        expect($hero->fresh()->json_content['title'])
            ->toContain('<strong>Sveiki</strong>')
            ->not->toContain('<script');

        $linkList = ContentPart::factory()->create([
            'content_id' => $this->content->id,
            'type' => 'link-list',
            'json_content' => ['title' => 'Nuorodos <2026> metams'],
        ]);

        expect($linkList->fresh()->json_content['title'])->toBe('Nuorodos <2026> metams');
    });

    /**
     * The tiptap block stores a JSON document, not HTML — markup only comes into
     * existence in the `html` accessor, so that is where it has to be cleaned.
     */
    test('html rendered from the tiptap json document is sanitized', function (): void {
        $part = ContentPart::factory()->create([
            'content_id' => $this->content->id,
            'type' => 'tiptap',
            'json_content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => 'Tekstas']],
                ], [
                    'type' => 'image',
                    'attrs' => ['src' => 'x', 'onerror' => 'alert(1)'],
                ]],
            ],
        ]);

        expect($part->fresh()->html)
            ->toContain('Tekstas')
            ->not->toContain('onerror');
    });
});
