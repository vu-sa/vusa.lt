<?php

namespace App\Settings;

use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

class DocumentSettings extends Settings
{
    /**
     * Array of content types that are considered "most important" and should
     * appear at the top of the document type filter.
     * Stored as JSON in the database and automatically cast to/from array by the package.
     *
     * @var string[]
     */
    public array $important_content_types = [];

    public static function group(): string
    {
        return 'documents';
    }

    /**
     * Get important content types as Collection
     */
    public function getImportantContentTypes(): Collection
    {
        return collect($this->important_content_types)->filter();
    }

    /**
     * Set important content types from array
     */
    public function setImportantContentTypes(array $types): void
    {
        $this->important_content_types = array_filter($types);
    }
}
