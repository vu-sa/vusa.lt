<?php

namespace App\Settings;

use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

class DocumentSettings extends Settings
{
    /** @var string[] */
    public array $important_content_types = [];

    /** @phpstan-var array<int, array{document_id: string, phrases: string[], enabled: bool, show_without_query: bool}> */
    public array $recommendations = [];

    public static function group(): string
    {
        return 'documents';
    }

    public function getImportantContentTypes(): Collection
    {
        return collect($this->important_content_types)->filter();
    }

    public function setImportantContentTypes(array $types): void
    {
        $this->important_content_types = array_filter($types);
    }
}
