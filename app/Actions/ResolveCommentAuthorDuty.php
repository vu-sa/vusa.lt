<?php

namespace App\Actions;

use App\Enums\SystemDutyType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\SystemDutyTypes;
use App\Support\InstitutionPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResolveCommentAuthorDuty
{
    public function __construct(private readonly SystemDutyTypes $systemDutyTypes) {}

    /**
     * Every duty the user has started, in the order the picker lists them: current before former,
     * a coordinator duty first, then by how prominent its institution is, then the newest.
     *
     * @return Collection<int, Dutiable>
     */
    public function options(User $user): Collection
    {
        $coordinatorTypeIds = $this->systemDutyTypes->idsWithDescendants(SystemDutyType::Coordinator);

        return $user->dutiables()
            ->whereDate('start_date', '<=', today())
            ->with(['duty.types', 'duty.institution.tenant', 'duty.institution.types'])
            ->get()
            ->filter(fn (Dutiable $option): bool => $option->duty !== null)
            ->sort(fn (Dutiable $a, Dutiable $b): int => self::rank($a, $coordinatorTypeIds) <=> self::rank($b, $coordinatorTypeIds))
            ->values();
    }

    /**
     * Only a current duty is suggested; a former one must be picked on purpose. A duty tied to the
     * record wins, then the author's last choice, then the first in picker order.
     */
    public function default(User $user, Model $commentable): ?string
    {
        $options = $this->options($user)->filter(fn (Dutiable $option): bool => self::isCurrent($option))->values();
        $related = $this->relatedTo($options, $commentable);

        if ($related !== null) {
            return $related->id;
        }

        $preferred = $user->ui_preferences['comment_dutiable_id'] ?? null;

        if (is_string($preferred) && $options->contains('id', $preferred)) {
            return $preferred;
        }

        return $options->first()?->id;
    }

    /**
     * @param  Collection<int, Dutiable>  $options  already in picker order
     */
    private function relatedTo(Collection $options, Model $commentable): ?Dutiable
    {
        if ($commentable instanceof Duty) {
            $sameDuty = $options->first(fn (Dutiable $option): bool => $option->duty_id === $commentable->getKey());

            if ($sameDuty !== null) {
                return $sameDuty;
            }
        }

        $institutionIds = collect(match (true) {
            $commentable instanceof Institution => [$commentable->getKey()],
            $commentable instanceof Duty => [$commentable->institution_id],
            $commentable instanceof Meeting => $commentable->institutions->modelKeys(),
            $commentable instanceof AgendaItem => $commentable->meeting?->institutions->modelKeys() ?? [],
            default => [],
        });

        return $options->first(fn (Dutiable $option): bool => $institutionIds->contains($option->duty?->institution_id));
    }

    /**
     * @param  array<int, int>  $coordinatorTypeIds
     * @return array<int, int>
     */
    private static function rank(Dutiable $option, array $coordinatorTypeIds): array
    {
        $institution = $option->duty?->institution;
        $isCoordinator = $option->duty?->types->contains(fn ($type): bool => in_array((int) $type->getKey(), $coordinatorTypeIds, true)) ?? false;

        return [
            self::isCurrent($option) ? 0 : 1,
            $isCoordinator ? 0 : 1,
            -($institution === null ? 0 : InstitutionPriority::of($institution)),
            // Newest first: a current duty by when it began, a former one by when it ended.
            -(self::isCurrent($option) ? $option->start_date : ($option->end_date ?? $option->start_date))->getTimestamp(),
        ];
    }

    public function forPost(User $user, Model $commentable, ?string $requested): ?string
    {
        if ($requested === null) {
            return $this->default($user, $commentable);
        }

        if (! $this->options($user)->contains('id', $requested)) {
            throw ValidationException::withMessages(['dutiable_id' => __('validation.exists', ['attribute' => 'dutiable_id'])]);
        }

        return $requested;
    }

    public static function isCurrent(Dutiable $dutiable): bool
    {
        return $dutiable->end_date === null || ! $dutiable->end_date->isBefore(today());
    }

    /**
     * `tenant` is the short label shown beside a name; the full institution name is for the picker.
     *
     * @return array{dutiable_id: string, name: string|null, institution: string|null, tenant: string|null, use_original_duty_name: bool, start_date: string, end_date: string|null, is_current: bool}
     */
    public static function data(Dutiable $dutiable): array
    {
        $duty = $dutiable->duty;
        $institution = $duty?->institution;
        $locale = app()->getLocale();

        return [
            'dutiable_id' => $dutiable->id,
            'name' => $duty?->getTranslation('name', $locale),
            'institution' => $institution?->getTranslation('name', $locale),
            'tenant' => $institution?->tenant?->shortname,
            'use_original_duty_name' => (bool) $dutiable->use_original_duty_name,
            'start_date' => $dutiable->start_date->toDateString(),
            'end_date' => $dutiable->end_date?->toDateString(),
            'is_current' => self::isCurrent($dutiable),
        ];
    }
}
