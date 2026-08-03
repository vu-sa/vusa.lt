<?php

namespace App\Support;

use App\Models\AgendaItemNote;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\ContentPart;
use App\Models\Document;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\Problem;
use App\Models\Tenant;
use App\Models\User;

/**
 * Extra typing hints for App\Services\ActivityChangeFormatter, for the cases
 * casts/heuristics can't infer on their own: rich-text/long-form columns
 * (which get a word diff rather than a flat before/after) and foreign-key
 * columns that should resolve to a display name rather than a raw id.
 *
 * Not meant to be exhaustive -- a model absent here still gets a sensible type
 * via ActivityChangeFormatter's cast/heuristic fallback chain. Extend as
 * models are added to the models covered by App\Support\Auditables.
 */
class ActivityFields
{
    /**
     * owner class => field => explicit type.
     *
     * @var array<class-string, array<string, string>>
     */
    public const OVERRIDES = [
        Problem::class => [
            'description' => 'diff',
            'solution' => 'diff',
            'steps_taken' => 'diff',
            'status' => 'enum',
        ],
        AgendaItemNote::class => [
            // Not 'diff': notes_html isn't translatable, so it doesn't need
            // Problem's raw-value logging fix, but it also hasn't been
            // exercised through diffDisplay() yet -- kept on the flat
            // "Content updated" placeholder until it has its own test pass.
            'notes_html' => 'rich',
        ],
        ContentPart::class => [
            // Excerpt, not the raw json_content it's derived from -- see
            // ContentPart::getContentSummaryAttribute(). Plain text already
            // (not HTML), see DIFF_HTML_SOURCED below.
            'content_summary' => 'diff',
            'options' => 'json',
        ],
    ];

    /**
     * 'diff' fields whose stored value is HTML and therefore needs the
     * HTML-to-plain-text pipeline (ActivityChangeFormatter::diffDisplay())
     * before diffing -- entity decoding, block-tag-to-space, strip_tags().
     * ContentPart::content_summary is deliberately absent: it is already
     * plain text via getSearchableContent(), and running entity-decoding
     * over it would silently rewrite a literal "&amp;" an author typed.
     *
     * @var array<class-string, list<string>>
     */
    public const DIFF_HTML_SOURCED = [
        Problem::class => ['description', 'solution', 'steps_taken'],
    ];

    /**
     * owner class => field => [target class, display attribute].
     *
     * @var array<class-string, array<string, array{0: class-string, 1: string}>>
     */
    public const RELATIONS = [
        Problem::class => [
            'responsible_user_id' => [User::class, 'name'],
        ],
        Duty::class => [
            'institution_id' => [Institution::class, 'name'],
        ],
        Document::class => [
            'institution_id' => [Institution::class, 'name'],
        ],
        News::class => [
            'category_id' => [Category::class, 'name'],
            // PairTranslatedRecord::execute() runs on every News save and
            // otherwise leaves a bare numeric id in the feed.
            'other_lang_id' => [News::class, 'title'],
        ],
        Page::class => [
            'category_id' => [Category::class, 'name'],
            'other_lang_id' => [Page::class, 'title'],
        ],
        Calendar::class => [
            'category_id' => [Category::class, 'name'],
        ],
    ];

    /**
     * Field name => [target class, display attribute], applied regardless of
     * owner class -- 'tenant_id' means the same thing everywhere in this app.
     *
     * @var array<string, array{0: class-string, 1: string}>
     */
    public const GENERIC_RELATIONS = [
        'tenant_id' => [Tenant::class, 'shortname'],
    ];

    /**
     * Fields never worth diffing even though the model logs them (large
     * binary/JSON blobs, timestamps already excluded globally, internal ids).
     *
     * @var list<string>
     */
    public const HIDDEN_KEYS = ['id', 'tenant_id'];
}
