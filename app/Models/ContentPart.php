<?php

namespace App\Models;

use App\Enums\ContentPartEnum;
use App\Models\Traits\LogsModelActivity;
use App\Services\ContentService;
use App\Services\HtmlSanitizerService;
use App\Tiptap\TiptapEditor;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $content_id
 * @property string $type
 * @property ArrayObject<array-key, mixed> $json_content
 * @property ArrayObject<array-key, mixed>|null $options
 * @property int $order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Content $content
 * @property-read string $content_summary
 * @property-read string|null $html
 * @property-read Collection<int, TextBoxSubmission> $textBoxSubmissions
 *
 * @method static \Database\Factories\ContentPartFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPart query()
 *
 * @mixin \Eloquent
 */
#[Appends(['html'])]
#[Fillable([
    'type',
    'json_content',
    'options',
    'order',
])]
class ContentPart extends Model
{
    use HasFactory, LogsModelActivity;

    /**
     * ContentPart declares #[Fillable] (guarded === ['*']), so
     * LogsModelActivity::defaultActivitylogOptions() picks logFillable(),
     * which would log the raw json_content blob (avg 5 KB, up to 300 KB) on
     * every body edit. Log the plain-text content_summary accessor instead --
     * it is diffable and renderable, json_content is neither.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultActivitylogOptions()
            ->logExcept(['json_content'])
            // Additive, not restrictive: attributesToBeLogged() merges fillable
            // + explicit attributes, so this registers the virtual accessor
            // below alongside type/options/order without dropping them.
            ->logOnly(['content_summary'])
            ->dontLogIfAttributesChangedOnly([
                // dontLogIfAttributesChangedOnly() REPLACES the parent's list
                // rather than extending it, so the global noise guards (see
                // config/activitylog.php) must be re-merged here.
                ...config('activitylog.default_except_attributes', []),
                // ContentService::updateContentParts() renumbers every
                // surviving block's `order` on each save -- without this, a
                // single reorder would log one near-identical entry per block.
                'order',
            ]);
    }

    /**
     * Plain-text excerpt for the activity log -- reuses getSearchableContent()
     * (already maintained per block type for Scout indexing) rather than a
     * second text extractor. Deliberately NOT in #[Appends]: that would ship
     * this on every public content payload, not just the activity log.
     */
    public function getContentSummaryAttribute(): string
    {
        return Str::limit(Str::squish($this->getSearchableContent()), 500);
    }

    /**
     * Get the attributes that should be cast.
     *
     * Using AsArrayObject for json_content to preserve JSON object structure.
     * This prevents empty objects {} from becoming empty arrays [] in the database,
     * which would break content types like Hero that expect object properties.
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'json_content' => AsArrayObject::class,
            'options' => AsArrayObject::class,
        ];
    }

    /**
     * `json_content` is persisted verbatim from the editor (see
     * {@see ContentService::updateContentParts()}), and several block types carry
     * raw HTML strings inside it that the public page renders with `v-html`:
     * accordion items and the person quote (`html`), and the hero title.
     * Sanitize those on write so every persistence path — controller, seeder,
     * DuplicateNewsAction — is covered.
     */
    #[\Override]
    protected static function booted(): void
    {
        static::saving(function (self $part): void {
            // Read through getAttribute(): a part being saved without content at
            // all is a NOT NULL violation the database should report, not a fatal
            // in this listener.
            $json = $part->getAttribute('json_content');

            if ($json instanceof ArrayObject) {
                $part->json_content = new ArrayObject($part->normalizeFormDataScalars($part->sanitizeJsonContentHtml($json->toArray())));
            }

            // options is nullable — absent stays absent.
            $options = $part->getAttribute('options');

            if ($options instanceof ArrayObject) {
                $part->options = new ArrayObject($part->normalizeFormDataScalars($options->toArray()));
            }
        });
    }

    /**
     * Option/content keys that hold booleans or integers but arrive as their string
     * equivalents ("1"/"0", "true"/"false", "8000") when the editor form was submitted
     * as FormData — Inertia's FormData serializer encodes every scalar that way (see
     * EditHomePage.vue's `forceFormData`). A stored "0" reads as truthy on the JS side
     * and flips the switch semantics, so normalize by key name on every write path.
     * Unlisted keys and unrecognizable values are left untouched.
     */
    private const FORMDATA_BOOLEAN_KEYS = [
        'autoplay', 'showNavigation', 'showThumbnails', 'showArrows', 'showIndicators',
        'showAvatar', 'showCaption', 'showLightbox', 'showIcon', 'showPlus', 'isClosed',
        'isTitleColored', 'is_active', 'allTenants', 'mobileStacking', 'equalHeight',
        'textLeft', 'imageLeft', 'rotation', 'overlayOverhang',
    ];

    private const FORMDATA_INTEGER_KEYS = ['autoplayDelay', 'limit', 'year', 'endNumber'];

    /**
     * @param  array<array-key, mixed>  $node
     * @return array<array-key, mixed>
     */
    private function normalizeFormDataScalars(array $node): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->normalizeFormDataScalars($value);

                continue;
            }

            if (! is_string($value) && ! is_int($value)) {
                continue;
            }

            if (in_array($key, self::FORMDATA_BOOLEAN_KEYS, true)) {
                // FILTER_NULL_ON_FAILURE returns null for non-boolean-ish strings —
                // keep the original value in that case.
                $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $node[$key] = $boolean ?? $value;
            } elseif (in_array($key, self::FORMDATA_INTEGER_KEYS, true) && is_numeric($value)) {
                $node[$key] = (int) $value;
            }
        }

        return $node;
    }

    /**
     * @param  array<array-key, mixed>  $content
     * @return array<array-key, mixed>
     */
    private function sanitizeJsonContentHtml(array $content): array
    {
        $sanitizer = app(HtmlSanitizerService::class);

        $walk = function (array $node) use (&$walk, $sanitizer): array {
            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $node[$key] = $walk($value);
                } elseif ($key === 'html' && is_string($value)) {
                    $node[$key] = $sanitizer->sanitizeRichContent($value);
                }
            }

            return $node;
        };

        $content = $walk($content);

        // HeroElement.vue renders json_content.title with `v-html` (authored with
        // the `compact` preset, a subset of the `full` allowlist). Only the hero's
        // own title — `title` on other blocks is plain text that sanitizing would
        // mangle the moment it contained a bare `<`.
        if ($this->type === 'hero' && isset($content['title']) && is_string($content['title'])) {
            $content['title'] = $sanitizer->sanitizeRichContent($content['title']);
        }

        return $content;
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function textBoxSubmissions(): HasMany
    {
        return $this->hasMany(TextBoxSubmission::class);
    }

    /**
     * Get pre-rendered HTML for TipTap content types.
     * Uses caching based on updated_at timestamp for automatic invalidation.
     */
    public function getHtmlAttribute(): ?string
    {
        // Only render HTML for tiptap-based content types
        if (! in_array($this->type, ['tiptap', 'shadcn-card'])) {
            return null;
        }

        // Use updated_at timestamp in cache key for automatic invalidation on edit
        $cacheKey = "content_part_html_{$this->id}_{$this->updated_at->timestamp}";

        return Cache::remember($cacheKey, 86400, fn () => $this->renderTiptapHtml());
    }

    /**
     * Render TipTap JSON content to HTML using the PHP TipTap editor.
     */
    protected function renderTiptapHtml(): string
    {
        try {
            $editor = new TiptapEditor;

            // Convert ArrayObject to plain array for TipTap PHP compatibility
            $content = $this->json_content->toArray();

            // The JSON document itself is not HTML, so the write-time mutator can
            // do nothing for this path — a crafted node (an <img> with onerror,
            // say) only becomes markup here, at render.
            return app(HtmlSanitizerService::class)
                ->sanitizeRichContent($editor->setContent($content)->getHTML());
        } catch (\Throwable $e) {
            // Log error but don't break the page - frontend will fallback to JS rendering
            \Log::warning("TipTap rendering failed for ContentPart {$this->id}: {$e->getMessage()}");

            return '';
        }
    }

    /**
     * Clear the HTML cache for this content part.
     */
    public function clearHtmlCache(): void
    {
        $cacheKey = "content_part_html_{$this->id}_{$this->updated_at->timestamp}";
        Cache::forget($cacheKey);
    }

    /**
     * Validate that the content type is valid
     */
    public function isValidType(): bool
    {
        try {
            // Attempt to create an enum from the type string
            ContentPartEnum::from($this->type);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Parse Tiptap elements to HTML
     */
    public function parseTiptapElements(): ContentPart
    {
        $editor = new TiptapEditor;
        $sanitizer = app(HtmlSanitizerService::class);

        if ($this->type === 'tiptap' || $this->type === 'shadcn-card') {
            $this->html = $sanitizer->sanitizeRichContent($editor->setContent($this->json_content)->getHTML());

            return $this;
        }

        if ($this->type === 'shadcn-accordion') {
            $json_content = $this->json_content;

            foreach ($json_content as $key => $value) {
                $json_content[$key]['html'] = $sanitizer->sanitizeRichContent(
                    $editor->setContent($value['content'])->getHTML()
                );
            }

            $this->json_content = $json_content;

            return $this;
        }

        return $this;
    }

    /**
     * Get searchable content
     */
    public function getSearchableContent(): string
    {
        $content = '';

        // Extract text content based on the type
        switch ($this->type) {
            case 'tiptap':
                // Extract text from Tiptap JSON
                $content = $this->extractTextFromTiptap($this->json_content);
                break;
            case 'shadcn-card':
                // Cards have title in options and content in json_content
                $content = $this->options['title'] ?? '';
                $content .= ' '.$this->extractTextFromTiptap($this->json_content);
                break;
            case 'shadcn-accordion':
                // Process each accordion item
                foreach ($this->json_content as $item) {
                    $content .= ($item['label'] ?? '').' ';
                    $content .= $this->extractTextFromTiptap($item['content'] ?? []);
                }
                break;
            case 'hero':
                // Extract text from hero section
                $content = ($this->json_content['title'] ?? '').' '.
                           ($this->json_content['description'] ?? '');
                foreach ($this->json_content['buttons'] ?? [] as $button) {
                    $content .= ' '.($button['text'] ?? '');
                }
                break;
            case 'news':
            case 'calendar':
                $content = $this->json_content['title'] ?? '';
                break;
            case 'carousel-slide-deck':
                foreach ($this->json_content as $slide) {
                    $content .= ($slide['title'] ?? '').' ';
                    $content .= ($slide['badge'] ?? '').' ';
                    $content .= $this->extractTextFromTiptap($slide['description'] ?? []).' ';
                }
                break;
            case 'hero-carousel':
                foreach ($this->json_content as $slide) {
                    $content .= ($slide['eyebrow'] ?? '').' ';
                    $content .= ($slide['title'] ?? '').' ';
                    $content .= ($slide['subtitle'] ?? '').' ';
                    $content .= $this->extractTextFromTiptap($slide['description'] ?? []).' ';
                    foreach ($slide['buttons'] ?? [] as $button) {
                        $content .= ($button['text'] ?? '').' ';
                    }
                }
                break;
            case 'card-stack':
                foreach ($this->json_content as $card) {
                    $content .= ($card['title'] ?? '').' ';
                    $content .= ($card['description'] ?? '').' ';
                }
                break;
            case 'photo-gallery':
                foreach ($this->json_content as $image) {
                    $content .= ($image['alt'] ?? '').' ';
                }
                break;
            case 'content-grid':
                $content = ($this->options['title'] ?? '').' '.($this->options['subtitle'] ?? '').' ';
                foreach ($this->json_content as $row) {
                    foreach ($row['columns'] ?? [] as $column) {
                        $columnContent = $column['content'] ?? [];
                        if (($columnContent['type'] ?? null) === 'tiptap') {
                            $content .= $this->extractTextFromTiptap($columnContent['value'] ?? []).' ';
                        } elseif (($columnContent['type'] ?? null) === 'image') {
                            $content .= ($columnContent['alt'] ?? '').' ';
                        } elseif (($columnContent['type'] ?? null) === 'card') {
                            $cardValue = $columnContent['value'] ?? [];
                            $content .= ($cardValue['title'] ?? '').' '.($cardValue['description'] ?? '').' ';
                        }
                    }
                }
                break;
            case 'link-list':
                // Author-written text only — resolved news/pages titles are already
                // indexed under their own News/Page documents and would go stale the
                // moment the referenced record is renamed.
                $content = ($this->options['title'] ?? '').' '.($this->options['subtitle'] ?? '');
                foreach ($this->json_content['links'] ?? [] as $link) {
                    $content .= ' '.($link['title'] ?? '');
                }
                break;
            case 'event-list':
                $content = ($this->options['title'] ?? '').' '.($this->options['subtitle'] ?? '').' '.
                           ($this->options['emptyMessage'] ?? '');
                break;
            case 'person-quote':
                $content = $this->extractTextFromTiptap($this->json_content['quote'] ?? []);
                $snapshot = $this->json_content['snapshot'] ?? [];
                $content .= ' '.($snapshot['name'] ?? '').' '.($snapshot['attribution'] ?? '');
                break;
            case 'section':
                // A marker block with no content of its own (see RichContentParser.vue's
                // groupedContent) — only its own title/subtitle are searchable text; the
                // blocks it wraps are indexed independently as their own parts.
                $content = ($this->options['title'] ?? '').' '.($this->options['subtitle'] ?? '');
                break;
            case 'timetable':
                $content = ($this->options['title'] ?? '').' ';
                foreach ($this->json_content ?? [] as $row) {
                    $content .= ($row['title'] ?? '').' ';
                }
                break;
        }

        return $content;
    }

    /**
     * Extract plain text from Tiptap JSON structure
     *
     * @param  mixed  $json
     */
    protected function extractTextFromTiptap($json): string
    {
        // The top-level call for 'tiptap'/'shadcn-card' passes $this->json_content
        // itself, which the AsArrayObject cast wraps in Illuminate's ArrayObject --
        // not a plain array, so it used to fail the is_array() check below and
        // silently return '' for the single most common block type. Nested calls
        // already pass plain arrays (json_decode never re-wraps children), so this
        // only ever has an effect on the outermost call.
        if ($json instanceof Arrayable) {
            $json = $json->toArray();
        }

        if (empty($json) || ! is_array($json)) {
            return '';
        }

        $text = '';

        // Handle direct text nodes
        if (isset($json['text'])) {
            $text .= $json['text'].' ';
        }

        // Recursively process content arrays
        if (isset($json['content']) && is_array($json['content'])) {
            foreach ($json['content'] as $item) {
                $text .= $this->extractTextFromTiptap($item).' ';
            }
        }

        return $text;
    }
}
