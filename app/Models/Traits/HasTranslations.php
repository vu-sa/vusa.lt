<?php

namespace App\Models\Traits;

use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

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
