<?php

namespace App\Http\Requests\Goals;

class UpdateGoalRequest extends GoalRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('goal'));
    }
}
