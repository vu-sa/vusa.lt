<?php

namespace App\Http\Requests\Content;

use App\Rules\SoftDeleteRules;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A bulk action over records picked in a collection. Every record must pass the policy
 * ability, so one record outside the actor's scope refuses the whole batch.
 *
 * @template TModel of Model
 */
abstract class BulkContentRequest extends FormRequest
{
    /** @var Collection<int, TModel>|null */
    private ?Collection $resolvedRecords = null;

    /** @return class-string<TModel> */
    abstract protected function modelClass(): string;

    /** The policy ability every selected record must pass. */
    abstract protected function ability(): string;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $ids = $this->input('ids');

        // Malformed or unknown ids are the validator's to report, not a 403.
        if (! is_array($ids) || $this->records()->count() !== count(array_unique($ids))) {
            return true;
        }

        return $this->records()->every(fn (Model $record): bool => $this->user()->can($this->ability(), $record));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', SoftDeleteRules::existsLive((new ($this->modelClass()))->getTable())],
        ];
    }

    /**
     * @return Collection<int, TModel>
     */
    public function records(): Collection
    {
        if ($this->resolvedRecords === null) {
            $ids = array_filter((array) $this->input('ids', []), is_numeric(...));
            $this->resolvedRecords = $this->modelClass()::query()->whereIn('id', $ids)->get();
        }

        return $this->resolvedRecords;
    }
}
