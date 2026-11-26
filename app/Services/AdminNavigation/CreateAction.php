<?php

namespace App\Services\AdminNavigation;

/**
 * A command palette entry that can also be offered as a workspace shortcut.
 *
 * The `target` vocabulary (`route` vs `screen`) matches
 * `resources/js/Composables/useActionWindowCatalog.ts`'s `ActionWindowAction['target']`, so a
 * future consumer swap is a data-source change, not a redesign.
 */
final readonly class CreateAction
{
    /**
     * @param  array{kind: 'route', routeName: string, routeParams?: array<string, string>}|array{kind: 'screen', screen: string}  $target
     */
    private function __construct(
        public string $key,
        public string $labelKey,
        public ?string $descriptionKey,
        public ?string $entityType,
        public array $target,
        public Visibility $visibility,
        public bool $paletteOnly,
        public bool $createsRecord,
    ) {}

    /** @param array<string, string> $routeParams */
    public static function route(
        string $key,
        string $labelKey,
        ?string $descriptionKey,
        ?string $entityType,
        string $routeName,
        Visibility $visibility,
        bool $paletteOnly = false,
        bool $createsRecord = true,
        array $routeParams = [],
    ): self {
        $target = ['kind' => 'route', 'routeName' => $routeName];
        if ($routeParams !== []) {
            $target['routeParams'] = $routeParams;
        }

        return new self($key, $labelKey, $descriptionKey, $entityType, $target, $visibility, $paletteOnly, $createsRecord);
    }

    public static function screen(
        string $key,
        string $labelKey,
        ?string $descriptionKey,
        ?string $entityType,
        string $screen,
        Visibility $visibility,
        bool $createsRecord = true,
    ): self {
        return new self($key, $labelKey, $descriptionKey, $entityType, ['kind' => 'screen', 'screen' => $screen], $visibility, false, $createsRecord);
    }

    /**
     * @return array{key: string, label: string, description: string|null, entityType: string|null, target: array<string, string|array<string, string>>}
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
