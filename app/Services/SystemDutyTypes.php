<?php

namespace App\Services;

use App\Enums\SystemDutyType;
use App\Models\DutyType;
use App\Settings\DutyTypeSettings;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves which duty type plays each {@see SystemDutyType} part: the one chosen in settings, or
 * the type with the old slug while nothing is chosen. Never look these types up by slug elsewhere.
 */
class SystemDutyTypes
{
    private const string CACHE_KEY = 'system_duty_types:v1';

    /** @var array<string, int|null>|null */
    private ?array $ids = null;

    public function id(SystemDutyType $part): ?int
    {
        return $this->ids()[$part->value] ?? null;
    }

    public function type(SystemDutyType $part): ?DutyType
    {
        $id = $this->id($part);

        return $id === null ? null : DutyType::query()->find($id);
    }

    /**
     * @return list<int>
     */
    public function idsWithDescendants(SystemDutyType ...$parts): array
    {
        return collect($parts)
            ->map(fn (SystemDutyType $part) => $this->type($part))
            ->filter()
            ->flatMap(fn (DutyType $type) => $type->getDescendantsAndSelf()->pluck('id'))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function slugsWithDescendants(SystemDutyType ...$parts): array
    {
        return DutyType::query()->whereKey($this->idsWithDescendants(...$parts))->pluck('slug')->filter()->values()->all();
    }

    /**
     * Slugs of the curator and mentor types and their subtypes; a programme belongs to these duties.
     *
     * @return list<string>
     */
    public function curatorSlugs(): array
    {
        return Cache::rememberForever(self::CACHE_KEY.':curator_slugs', fn (): array => $this->slugsWithDescendants(SystemDutyType::Curator, SystemDutyType::Mentor));
    }

    /**
     * Parts a type plays, so it cannot be deleted from under the contact pages.
     *
     * @return list<SystemDutyType>
     */
    public function partsOf(DutyType $type): array
    {
        return array_values(array_filter(SystemDutyType::cases(), fn (SystemDutyType $part): bool => $this->id($part) === (int) $type->id));
    }

    public function flush(): void
    {
        $this->ids = null;
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.':curator_slugs');
    }

    /**
     * @return array<string, int|null>
     */
    private function ids(): array
    {
        return $this->ids ??= Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = app(DutyTypeSettings::class);
            $ids = [];

            foreach (SystemDutyType::cases() as $part) {
                $configured = $settings->{$part->settingKey()};
                $ids[$part->value] = ($configured !== null && DutyType::query()->whereKey($configured)->exists())
                    ? (int) $configured
                    : DutyType::query()->where('slug', $part->fallbackSlug())->value('id');
            }

            return $ids;
        });
    }
}
