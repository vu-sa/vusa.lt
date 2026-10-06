<?php

namespace App\Http\Requests\Goals;

use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Linking says the goal works on the problem, so the goal's editors decide it; the problem stays
 * readable to everyone either way, and its own editors can still log steps on it.
 */
class LinkGoalProblemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('goal'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'problem_id' => ['required', 'string', SoftDeleteRules::existsLive('problems')],
        ];
    }
}
