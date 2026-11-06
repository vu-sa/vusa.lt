<?php

namespace App\Http\Requests\Concerns;

use App\Contracts\ImageMediaOwner;
use App\Rules\AttachableImageMedia;
use Illuminate\Database\Eloquent\Model;

trait HasImageValidation
{
    /** @param list<string> $additionalMimes */
    protected function singleImageRules(
        int $maxMB = 10,
        bool $required = false,
        array $additionalMimes = []
    ): array {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = 'image';
        $rules[] = 'max:'.($maxMB * 1024);

        $mimes = array_merge(['jpeg', 'jpg', 'png', 'webp'], $additionalMimes);
        $rules[] = 'mimes:'.implode(',', array_unique($mimes));
        $rules[] = 'extensions:'.implode(',', array_unique($mimes));

        return $rules;
    }

    /** @param list<string> $additionalMimes */
    protected function galleryImageRules(
        int $maxMB = 5,
        ?int $maxFiles = 20,
        array $additionalMimes = []
    ): array {
        $rules = [];

        $rules[] = 'image';
        $rules[] = 'max:'.($maxMB * 1024);

        $mimes = array_merge(['jpeg', 'jpg', 'png', 'webp'], $additionalMimes);
        $rules[] = 'mimes:'.implode(',', array_unique($mimes));
        $rules[] = 'extensions:'.implode(',', array_unique($mimes));

        return $rules;
    }

    protected function imagesArrayRules(?int $maxFiles = 20): array
    {
        $rules = ['nullable', 'array'];

        if ($maxFiles !== null) {
            $rules[] = 'max:'.$maxFiles;
        }

        return $rules;
    }

    /**
     * An ImageData object posted back by an ImageUpload in media mode; null removes the image.
     * Pass the record, or an unsaved instance when creating one.
     *
     * @return array<string, mixed>
     */
    protected function imageMediaRules(string $key, Model&ImageMediaOwner $owner, string $collection): array
    {
        return [
            $key => ['nullable', 'array'],
            "{$key}.id" => ['nullable', 'integer', new AttachableImageMedia($owner, $collection)],
            "{$key}.focal_point" => ['nullable', 'string', 'max:20', 'regex:/^\d{1,3}(\.\d+)?% \d{1,3}(\.\d+)?%$/'],
            "{$key}.alt" => ['nullable', 'string', 'max:255'],
            "{$key}.author" => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function imageValidationMessages(): array
    {
        return [
            'main_image.required' => trans('forms.validation.image.main_required'),
            'main_image.image' => trans('forms.validation.image.must_be_image'),
            'main_image.max' => trans('forms.validation.image.max'),
            'main_image.mimes' => trans('forms.validation.image.mimes'),

            'images.max' => trans('forms.validation.image.count_max'),
            'images.*.image' => trans('forms.validation.image.all_must_be_images'),
            'images.*.max' => trans('forms.validation.image.each_max'),
            'images.*.mimes' => trans('forms.validation.image.all_mimes'),
        ];
    }
}
