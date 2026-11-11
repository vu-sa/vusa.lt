<?php

namespace App\Services\AdminNavigation;

final readonly class SectionLink
{
    public function __construct(
        public string $key,
        public string $labelKey,
        public string $routeName,
        public Visibility $visibility,
    ) {}

    /** @return array{key: string, label: string, routeName: string} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->labelKey,
            'routeName' => $this->routeName,
        ];
    }
}
