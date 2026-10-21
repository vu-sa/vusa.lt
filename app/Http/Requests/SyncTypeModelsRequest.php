<?php

namespace App\Http\Requests;

use App\Actions\Responsibilities\AssignableDutyTypes;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\InstitutionType;
use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncTypeModelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('type');

        return ($type instanceof InstitutionType || $type instanceof DutyType)
            && ($this->user()?->can('update', $type) ?? false);
    }

    public function rules(): array
    {
        $relation = $this->route('type') instanceof InstitutionType ? 'institutions' : 'duties';

        return [
            'models' => ['present', 'array'],
            'models.*' => ['string', 'distinct', SoftDeleteRules::existsLive($relation)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = $this->route('type');

            if (! $type instanceof DutyType || $validator->errors()->isNotEmpty()) {
                return;
            }

            $current = $type->duties()->pluck('duties.id');
            $requested = collect($this->input('models', []));
            $changed = $requested->diff($current)->merge($current->diff($requested));

            if ($changed->isEmpty()) {
                return;
            }

            abort_unless(AssignableDutyTypes::allowsChanges($this->user(), [], [$type->id]), 403, __('types.assignment_forbidden'));

            foreach (Duty::query()->whereKey($changed->all())->with('tenants')->get() as $duty) {
                abort_unless($this->user()->can('update', $duty), 403);
            }
        }];
    }
}
