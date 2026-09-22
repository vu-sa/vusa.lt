<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\MergeCandidatesRequest;
use App\Models\Duty;
use App\Models\StudyProgram;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MergeCandidateApiController extends ApiController
{
    public function index(MergeCandidatesRequest $request): JsonResponse
    {
        $type = (string) $request->validated('type');
        $sourceIds = collect($request->validated('source_ids'))->map(fn (mixed $id): string => (string) $id)->values();
        $searchQuery = (string) $request->validated('query');
        $actor = $this->requireAuth($request);
        $modelClass = $this->modelClass($type);

        /** @var Collection<int, Model> $sources */
        $sources = $modelClass::query()->whereIn('id', $sourceIds)->get();

        if ($sources->count() !== $sourceIds->count()) {
            throw ValidationException::withMessages(['source_ids' => __('validation.exists', ['attribute' => 'source ids'])]);
        }

        if ($sources->contains(fn (Model $source): bool => ! $actor->can('delete', $source))) {
            return $this->jsonForbidden();
        }

        $candidates = $this->candidateQuery($type, $searchQuery)
            ->whereNotIn('id', $sourceIds)
            ->limit(100)
            ->get()
            ->filter(fn (Model $candidate): bool => $actor->can('update', $candidate))
            ->take(20)
            ->map(fn (Model $candidate): array => $this->candidate($type, $candidate))
            ->values();

        return $this->jsonSuccess($candidates);
    }

    /** @return class-string<Model> */
    private function modelClass(string $type): string
    {
        return match ($type) {
            'users' => User::class,
            'duties' => Duty::class,
            'study-programs' => StudyProgram::class,
            'tags' => Tag::class,
            default => throw new \InvalidArgumentException("Unknown merge candidate type: {$type}"),
        };
    }

    /** @return Builder<Duty>|Builder<StudyProgram>|Builder<Tag>|Builder<User> */
    private function candidateQuery(string $type, string $query): Builder
    {
        return match ($type) {
            'users' => User::query()->where(fn (Builder $builder) => $builder
                ->whereLike('name', "%{$query}%", false)
                ->orWhereLike('email', "%{$query}%", false))->orderBy('name'),
            'duties' => Duty::query()->with('institution:id,name')->whereLike('name', "%{$query}%", false)->orderBy('name'),
            'study-programs' => StudyProgram::query()->with('tenant:id,shortname')->whereLike('name', "%{$query}%", false)->orderBy('name'),
            'tags' => Tag::query()->where(fn (Builder $builder) => $builder
                ->whereLike('name', "%{$query}%", false)
                ->orWhereLike('alias', "%{$query}%", false))->orderBy('alias'),
            default => throw new \InvalidArgumentException("Unknown merge candidate type: {$type}"),
        };
    }

    /** @return array{id: string|int, label: string, context: string|null} */
    private function candidate(string $type, Model $candidate): array
    {
        return match ($type) {
            'users' => [
                'id' => $candidate->getKey(),
                'label' => $candidate instanceof User ? $candidate->name : '',
                'context' => $candidate instanceof User ? $candidate->email : null,
            ],
            'duties' => [
                'id' => $candidate->getKey(),
                'label' => $candidate instanceof Duty ? $this->localized($candidate->name) : '',
                'context' => $candidate instanceof Duty ? $candidate->institution?->name : null,
            ],
            'study-programs' => [
                'id' => $candidate->getKey(),
                'label' => $candidate instanceof StudyProgram ? $candidate->name : '',
                'context' => $candidate instanceof StudyProgram ? $candidate->tenant->shortname : null,
            ],
            'tags' => [
                'id' => $candidate->getKey(),
                'label' => $candidate instanceof Tag ? $this->localized($candidate->name) : '',
                'context' => $candidate instanceof Tag ? $candidate->alias : null,
            ],
            default => throw new \InvalidArgumentException("Unknown merge candidate type: {$type}"),
        };
    }

    private function localized(mixed $value): string
    {
        if (is_array($value)) {
            return (string) ($value[app()->getLocale()] ?? $value['lt'] ?? $value['en'] ?? '');
        }

        return (string) $value;
    }
}
