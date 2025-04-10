<?php

namespace App\Models;

use App\Enums\ContentPartEnum;
use App\Tiptap\TiptapEditor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPart extends Model
{
    use HasFactory;

    protected $casts = [
        'json_content' => 'array',
        'options' => 'array',
    ];

    protected $fillable = [
        'type',
        'json_content',
        'options',
        'order',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * Validate that the content type is valid
     *
     * @return bool
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
     *
     * @return ContentPart
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
     * 
     * @return string
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
                $content .= ' ' . $this->extractTextFromTiptap($this->json_content);
                break;
            case 'shadcn-accordion':
                // Process each accordion item
                foreach ($this->json_content as $item) {
                    $content .= ($item['label'] ?? '') . ' ';
                    $content .= $this->extractTextFromTiptap($item['content'] ?? []);
                }
                break;
            case 'hero':
                // Extract text from hero section
                $content = ($this->json_content['title'] ?? '') . ' ' . 
                           ($this->json_content['subtitle'] ?? '') . ' ' .
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
     * @param array $json
     * @return string
     */
    protected function extractTextFromTiptap($json): string
    {
        if (empty($json) || !is_array($json)) {
            return '';
        }

        $text = '';

        // Handle direct text nodes
        if (isset($json['text'])) {
            $text .= $json['text'] . ' ';
        }

        // Recursively process content arrays
        if (isset($json['content']) && is_array($json['content'])) {
            foreach ($json['content'] as $item) {
                $text .= $this->extractTextFromTiptap($item) . ' ';
            }
        }

        return $text;
    }
}
