<?php

namespace App\Http\Requests\Concerns;

/**
 * Standardized image validation rules for form requests.
 *
 * Usage:
 *   use HasImageValidation;
 *
 *   public function rules(): array
 *   {
 *       return [
 *           'main_image' => $this->singleImageRules(required: true),
 *           'images.*' => $this->galleryImageRules(),
 *       ];
 *   }
 */
trait HasImageValidation
{
    /**
     * Get validation rules for a single image upload.
     *
     * @param  int  $maxMB  Maximum file size in MB
     * @param  bool  $required  Whether the image is required
     * @param  array  $additionalMimes  Additional mime types to allow
     */
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

        return $rules;
    }

    /**
     * Get validation rules for gallery/multiple image uploads.
     *
     * @param  int  $maxMB  Maximum file size in MB per image
     * @param  int|null  $maxFiles  Maximum number of files allowed (null for unlimited)
     * @param  array  $additionalMimes  Additional mime types to allow
     */
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

        return $rules;
    }

    /**
     * Get validation rules for the images array itself.
     *
     * @param  int|null  $maxFiles  Maximum number of files allowed
     */
    protected function imagesArrayRules(?int $maxFiles = 20): array
    {
        $rules = ['nullable', 'array'];

        if ($maxFiles !== null) {
            $rules[] = 'max:'.$maxFiles;
        }

        return $rules;
    }

    /**
     * Get all image validation messages.
     */
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
