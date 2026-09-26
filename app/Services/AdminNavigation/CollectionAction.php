<?php

namespace App\Services\AdminNavigation;

/** A record-list action exposed by the navigation catalogue. */
final readonly class CollectionAction
{
    private function __construct(
        public string $key,
        public string $labelKey,
        public string $target,
        public Visibility $visibility,
    ) {}

    public static function merge(Visibility $visibility): self
    {
        return new self('merge', 'shell.actions.merge.title', 'merge', $visibility);
    }

    /** @return array{key: string, label: string, target: string} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->labelKey,
            'target' => $this->target,
        ];
    }
}
