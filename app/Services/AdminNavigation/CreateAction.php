<?php

namespace App\Services\AdminNavigation;

/**
 * A shortcut offered from **+ Sukurti**, the workspace picker or a Pradžia tile.
 *
 * The `target` vocabulary (`route` vs `screen`) matches
 * `resources/js/Composables/useActionWindowCatalog.ts`'s `ActionWindowAction['target']`, so a
 * future consumer swap is a data-source change, not a redesign.
 */
final readonly class CreateAction
{
    /**
     * @param  array{kind: 'route', routeName: string}|array{kind: 'screen', screen: string}  $target
     */
    private function __construct(
        public string $key,
        public string $labelKey,
        public ?string $descriptionKey,
        public ?string $entityType,
        public array $target,
        public Visibility $visibility,
    ) {}

    public static function route(
        string $key,
        string $labelKey,
        ?string $descriptionKey,
        ?string $entityType,
        string $routeName,
        Visibility $visibility,
    ): self {
        return new self($key, $labelKey, $descriptionKey, $entityType, ['kind' => 'route', 'routeName' => $routeName], $visibility);
    }

    public static function screen(
        string $key,
        string $labelKey,
        ?string $descriptionKey,
        ?string $entityType,
        string $screen,
        Visibility $visibility,
    ): self {
        return new self($key, $labelKey, $descriptionKey, $entityType, ['kind' => 'screen', 'screen' => $screen], $visibility);
    }

    /**
     * @return array{key: string, label: string, description: string|null, entityType: string|null, target: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->labelKey,
            'description' => $this->descriptionKey,
            'entityType' => $this->entityType,
            'target' => $this->target,
        ];
    }
}
