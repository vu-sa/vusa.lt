<?php

namespace App\Http\Requests\Goals;

use App\Models\Goal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Linking writes a step onto the goal, so the goal's editors decide it; the agenda item only has
 * to be one the user can open.
 */
class LinkAgendaItemGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('viewSummary', $this->route('agendaItem'))) {
            return false;
        }

        $goal = Goal::query()->find($this->input('goal_id'));

        // An unknown goal is the `exists` rule's to report, not a 403.
        return $goal === null || $this->user()->can('update', $goal);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'goal_id' => ['required', 'string', 'exists:goals,id'],
        ];
    }
}
