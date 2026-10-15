<?php

namespace App\Http\Requests\Goals;

use App\Models\Document;
use App\Models\Goal;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Step;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * A step is written from its goal's or its problem's page and authorizes against that parent.
 * It may also count towards the other side, but only one already linked to the parent.
 */
class StepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->parent());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parent = $this->parent();

        return [
            'title.lt' => 'required_without:title.en|nullable|string|max:255',
            'title.en' => 'required_without:title.lt|nullable|string|max:255',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'happened_on' => 'required|date',
            'performers' => 'nullable|array|max:20',
            'performers.*' => ['string', SoftDeleteRules::existsLive('users')],
            'agenda_item_id' => ['nullable', 'string', 'exists:agenda_items,id', $this->openableReference('agenda_item_id', AgendaItem::class, 'viewSummary')],
            'document_id' => ['nullable', 'integer', 'exists:documents,id', $this->openableReference('document_id', Document::class, 'view')],
            'url' => 'nullable|url:http,https|max:2048',
            ...($parent instanceof Goal
                ? ['problem_id' => ['nullable', 'string', Rule::in($parent->problems()->pluck('problems.id')->all())]]
                : ['goal_id' => ['nullable', 'string', Rule::in($parent->goals()->whereHas('tenant', fn ($query) => $query->where('goals_enabled', true))->pluck('goals.id')->all())]]),
        ];
    }

    #[\Override]
    public function messages(): array
    {
        return [
            'title.lt.required_without' => trans('goals.validation.title_required'),
            'title.en.required_without' => trans('goals.validation.title_required'),
            'problem_id.in' => trans('goals.validation.link_first'),
            'goal_id.in' => trans('goals.validation.link_first'),
        ];
    }

    /**
     * A step may only point at something its author can open. A reference the step already has
     * passes unchanged, so a colleague who cannot see it may still edit the rest of the step.
     *
     * @param  class-string<Model>  $class
     */
    private function openableReference(string $attribute, string $class, string $ability): \Closure
    {
        return function (string $key, mixed $value, \Closure $fail) use ($attribute, $class, $ability): void {
            $step = $this->route('step');

            if ($step instanceof Step && (string) $step->{$attribute} === (string) $value) {
                return;
            }

            $model = $class::query()->find($value);

            if ($model !== null && ! Gate::allows($ability, $model)) {
                $fail(trans('goals.validation.reference_hidden'));
            }
        };
    }

    public function parent(): Goal|Problem
    {
        $goal = $this->route('goal');

        return $goal instanceof Goal ? $goal : $this->route('problem');
    }
}
