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
     * Get all image validation messages in Lithuanian.
     */
    protected function imageValidationMessages(): array
    {
        return [
            'main_image.required' => 'Pagrindinė nuotrauka yra privaloma.',
            'main_image.image' => 'Failas turi būti paveikslėlis.',
            'main_image.max' => 'Paveikslėlis negali būti didesnis nei :max KB.',
            'main_image.mimes' => 'Paveikslėlis turi būti JPEG, PNG arba WebP formato.',

            'images.max' => 'Galima įkelti ne daugiau kaip :max nuotraukų.',
            'images.*.image' => 'Visi failai turi būti paveikslėliai.',
            'images.*.max' => 'Kiekvienas paveikslėlis negali būti didesnis nei :max KB.',
            'images.*.mimes' => 'Paveikslėliai turi būti JPEG, PNG arba WebP formato.',
        ];
    }
}
