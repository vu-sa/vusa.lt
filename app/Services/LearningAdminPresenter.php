<?php

namespace App\Services;

use App\Actions\GetReceivedHandoverNotes;
use App\Actions\ResolveLearningPath;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\GlossaryTerm;
use App\Models\HandoverNote;
use App\Models\User;
use Illuminate\Support\Collection;

class LearningAdminPresenter
{
    public static function canSeeReps(User $user): bool
    {
        return $user->isSuperAdmin() || app(ResponsibilityResolver::class)->holdsAnywhere($user, ResponsibilityPurpose::StudentRepCoordination);
    }

    /** @return array{id: string, name: string} */
    public static function member(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name];
    }

    /** @return list<array{id: string, term: string, definition: string}> */
    public static function glossary(string $locale): array
    {
        $result = [];
        foreach (GlossaryTerm::query()->get() as $term) {
            /** @var GlossaryTerm $term */
            $result[] = ['id' => $term->id, 'term' => (string) $term->getTranslation('term', $locale), 'definition' => (string) $term->getTranslation('definition', $locale)];
        }

        return $result;
    }

    /** @return array{id: string, dutyId: string, duty: string, author: array{id: string, name: string}, body: string, createdAt: string|null, updatedAt: string|null} */
    public static function note(HandoverNote $note, string $locale): array
    {
        return [
            'id' => $note->id, 'dutyId' => $note->duty_id, 'duty' => (string) $note->duty->getTranslation('name', $locale),
            'author' => ['id' => $note->author->id, 'name' => $note->author->name], 'body' => $note->body,
            'createdAt' => $note->created_at?->toIso8601String(), 'updatedAt' => $note->updated_at?->toIso8601String(),
        ];
    }

    /** @return list<array{dutyId: string, title: string, note: array{id: string, dutyId: string, duty: string, author: array{id: string, name: string}, body: string, createdAt: string|null, updatedAt: string|null}|null}> */
    public static function writable(User $user, string $locale): array
    {
        $result = [];
        $duties = $user->duties()->wherePivot('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('dutiables.end_date')->orWhere('dutiables.end_date', '>=', today()->subDays(60)))
            ->get()->unique('id')->values();
        $notes = HandoverNote::query()->where('user_id', $user->id)->whereIn('duty_id', $duties->pluck('id'))->with(['duty', 'author'])->latest()->get();
        foreach ($duties as $duty) {
            if ($user->can('create', [HandoverNote::class, $duty])) {
                $note = $notes->firstWhere('duty_id', $duty->id);
                $result[] = ['dutyId' => $duty->id, 'title' => (string) $duty->getTranslation('name', $locale), 'note' => $note ? self::note($note, $locale) : null];
            }
        }

        return $result;
    }

    /** @return list<array{id: string, dutyId: string, duty: string, author: array{id: string, name: string}, body: string, createdAt: string|null, updatedAt: string|null}> */
    public static function received(User $user, string $locale): array
    {
        $result = [];
        foreach (GetReceivedHandoverNotes::execute($user) as $note) {
            $result[] = self::note($note, $locale);
        }

        return $result;
    }

    /**
     * @param  Collection<int, User>  $users
     * @return list<array{id: string, name: string, institutions: list<array{id: string, title: string}>, totals: array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}}>
     */
    public static function representatives(Collection $users, string $locale): array
    {
        $paths = ResolveLearningPath::forUsers($users);
        $dutyIds = $users->flatMap(fn (User $user) => $user->authorization_duties->pluck('id'))->unique();
        $repDutyIds = Duty::query()->whereIn('id', $dutyIds)->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)->pluck('id');
        $result = [];
        foreach ($users as $user) {
            $institutions = [];
            foreach ($user->authorization_duties->whereIn('id', $repDutyIds)->pluck('institution')->filter()->unique('id') as $institution) {
                $institutions[] = ['id' => $institution->id, 'title' => (string) $institution->getTranslation('name', $locale)];
            }
            $result[] = ['id' => $user->id, 'name' => $user->name, 'institutions' => $institutions, 'totals' => $paths->get($user->id)->totals()];
        }

        return $result;
    }

    /**
     * @param  list<array{id: string, name: string, institutions: list<array{id: string, title: string}>, totals: array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}}>  $reps
     * @return array{representatives: int, essentialDone: int, essentialTotal: int, allDone: int, allTotal: int}
     */
    public static function summary(array $reps): array
    {
        $summary = ['representatives' => count($reps), 'essentialDone' => 0, 'essentialTotal' => 0, 'allDone' => 0, 'allTotal' => 0];
        foreach ($reps as $rep) {
            foreach ($rep['totals'] as $key => $value) {
                $summary[$key] += $value;
            }
        }

        return $summary;
    }
}
