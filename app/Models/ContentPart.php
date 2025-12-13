<?php

namespace App\Models;

use App\Enums\ContentPartEnum;
use App\Tiptap\TiptapEditor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $content_id
 * @property string $type
 * @property \Illuminate\Database\Eloquent\Casts\ArrayObject<array-key, mixed> $json_content
 * @property \Illuminate\Database\Eloquent\Casts\ArrayObject<array-key, mixed>|null $options
 * @property int $order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\Content $content
 * @property mixed $html
 *
 * @method static \Database\Factories\ContentPartFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentPart query()
 *
 * @mixin \Eloquent
 */
class ContentPart extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * Using AsArrayObject for json_content to preserve JSON object structure.
     * This prevents empty objects {} from becoming empty arrays [] in the database,
     * which would break content types like Hero that expect object properties.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'json_content' => \Illuminate\Database\Eloquent\Casts\AsArrayObject::class,
            'options' => \Illuminate\Database\Eloquent\Casts\AsArrayObject::class,
        ];
    }

    protected $fillable = [
        'type',
        'json_content',
        'options',
        'order',
    ];

    // Add property for HTML content
    protected $appends = ['html'];

    // Add a default value for html attribute
    protected $html = '';

    public function content(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    // Add getter for html attribute
    public function getHtmlAttribute()
    {
        return $this->html ?? '';
    }

    // Add setter for html attribute
    public function setHtmlAttribute($value)
    {
        $this->html = $value;
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
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Parse Tiptap elements to HTML
     */
    public function parseTiptapElements(): ContentPart
    {
        $editor = new TiptapEditor;

        if ($this->type === 'tiptap' || $this->type === 'shadcn-card') {
            $this->html = $editor->setContent($this->json_content)->getHTML();

            return $this;
        }

        if ($this->type === 'shadcn-accordion') {
            $json_content = $this->json_content;

            foreach ($json_content as $key => $value) {
                $json_content[$key]['html'] = $editor->setContent($value['content'])->getHTML();
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
                           ($this->json_content['subtitle'] ?? '').' '.
                           ($this->json_content['buttonText'] ?? '');
                break;
            case 'news':
            case 'calendar':
                $content = $this->json_content['title'] ?? '';
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
