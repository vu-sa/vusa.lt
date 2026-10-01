<?php

namespace App\Http\Requests;

use App\Models\InstitutionType;
use App\Models\DutyType;
use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

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
}
