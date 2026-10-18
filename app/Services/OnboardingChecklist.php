<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

class OnboardingChecklist
{
    public const string KEY = 'getting-started-v1';

    /** @return array<string, array{route: ?string, skippable: bool}> */
    public function items(): array
    {
        return [
            'access' => ['route' => 'profile.roles', 'skippable' => false],
            'contacts' => ['route' => 'profile', 'skippable' => false],
            'preferences' => ['route' => 'profile.notifications', 'skippable' => false],
            'installation' => ['route' => null, 'skippable' => true],
            'push' => ['route' => null, 'skippable' => true],
            'orientation' => ['route' => 'tasks.index', 'skippable' => false],
        ];
    }

    public function present(User $user): array
    {
        $progress = $user->onboarding_progress[self::KEY] ?? [];
        $items = collect($this->items())->map(function (array $definition, string $key) use ($progress): array {
            return [
                'key' => $key,
                'title' => "onboarding.items.{$key}.title",
                'description' => "onboarding.items.{$key}.description",
                'actionLabel' => "onboarding.items.{$key}.action",
                'confirmLabel' => "onboarding.items.{$key}.confirm",
                'skipLabel' => $definition['skippable'] ? "onboarding.items.{$key}.skip" : null,
                'href' => $definition['route'] !== null ? route($definition['route']) : null,
                'status' => $progress['items'][$key]['status'] ?? 'pending',
            ];
        })->values();
        $resolved = $items->where('status', '!=', 'pending')->count();
        $next = $items->firstWhere('status', 'pending');

        return [
            'key' => self::KEY,
            'title' => 'onboarding.title',
            'items' => $items->all(),
            'resolved' => $resolved,
            'total' => $items->count(),
            'complete' => $resolved === $items->count(),
            'hidden' => (bool) ($progress['hidden'] ?? false),
            'href' => route('onboarding.index'),
            'nextHref' => route('onboarding.index', $next ? ['item' => $next['key']] : []),
        ];
    }

    public function updateItem(User $user, string $item, string $status): array
    {
        abort_unless(array_key_exists($item, $this->items()), 404);

        return $this->mutate($user, function (array $progress) use ($item, $status): array {
            $progress['items'][$item] = ['status' => $status, 'updatedAt' => now()->toIso8601String()];

            return $progress;
        });
    }

    public function setHidden(User $user, bool $hidden): array
    {
        return $this->mutate($user, fn (array $progress): array => [...$progress, 'hidden' => $hidden]);
    }

    private function mutate(User $user, Closure $update): array
    {
        return DB::transaction(function () use ($user, $update): array {
            // Lock the fresh value so two tabs cannot overwrite different checklist decisions.
            $actor = User::query()->lockForUpdate()->findOrFail($user->id);
            $progress = $actor->onboarding_progress ?? [];
            $progress[self::KEY] = $update($progress[self::KEY] ?? []);
            $actor->onboarding_progress = $progress;
            $actor->saveQuietly();
            $user->onboarding_progress = $progress;

            return $this->present($actor);
        });
    }
}
