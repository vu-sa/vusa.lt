<?php

namespace App\Models\Traits;

use App\Services\HtmlSanitizerService;
use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations {
        setTranslation as protected baseSetTranslation;
    }

    /**
     * Translatable fields holding Tiptap `full` preset HTML that is later rendered
     * with `v-html` / `{!! !!}`. Models listing a field here get it sanitized on
     * write; the default empty list makes this a no-op for everything else.
     *
     * @return list<string>
     */
    protected function sanitizedHtmlTranslations(): array
    {
        return [];
    }

    /**
     * Sanitize on write. Spatie funnels every write path — mass assignment,
     * `update()`, `setTranslations()` — through `setTranslation()`, so overriding
     * it here covers them all. An `Attribute` mutator would not fire at all,
     * because translatable attributes never reach `setAttribute()`'s parent call.
     */
    public function setTranslation(string $key, string $locale, $value): self
    {
        if (is_string($value) && in_array($key, $this->sanitizedHtmlTranslations(), true)) {
            $value = app(HtmlSanitizerService::class)->sanitizeRichContent($value);
        }

        return $this->baseSetTranslation($key, $locale, $value);
    }

    /**
     * Return attributes with translations of the model.
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();
        foreach ($this->getTranslatableAttributes() as $field) {

            // If field is not selected, this makes it so nothing is returned, instead
            // of empty string
            if (! isset($attributes[$field])) {
                continue;
            }

            $attributes[$field] = $this->getTranslation($field, app()->getLocale());
        }

        return $attributes;
    }

    /**
     * Return full attributes of the model.
     */
    public function toFullArray(): array
    {
        $attributes = parent::toArray();
        foreach ($this->getTranslatableAttributes() as $field) {
            $attributes[$field] = $this->getTranslations($field);

            // check if empty array, if so, set lt and en to empty string
            if (empty($attributes[$field])) {
                $attributes[$field] = [
                    'lt' => '',
                    'en' => '',
                ];
            }
        }

        return $attributes;
    }
}
