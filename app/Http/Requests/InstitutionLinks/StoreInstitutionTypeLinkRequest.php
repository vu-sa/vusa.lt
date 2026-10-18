<?php

namespace App\Http\Requests\InstitutionLinks;

use App\Enums\InstitutionRelationKind;
use App\Enums\InstitutionTypeLinkScope;
use App\Models\InstitutionType;
use App\Models\InstitutionTypeLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Links the type in the URL with another type — or with itself, which relates same-type institutions.
 */
class StoreInstitutionTypeLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InstitutionTypeLink::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'other_type_id' => ['required', 'integer', Rule::exists('institution_types', 'id')->withoutTrashed()],
            'direction' => ['required', Rule::in(['outgoing', 'incoming'])],
            'kind' => ['required', Rule::enum(InstitutionRelationKind::class)],
            'mutual' => ['boolean'],
            'scope' => ['sometimes', Rule::enum(InstitutionTypeLinkScope::class)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && InstitutionTypeLink::query()->where($this->pairKey())->exists()) {
                    $validator->errors()->add('other_type_id', __('Toks tipų ryšys jau yra. Redaguok esamą ryšį.'));
                }
            },
        ];
    }

    /**
     * @return array{source_type_id: int, target_type_id: int, kind: string, mutual: bool, scope: string}
     */
    public function linkAttributes(): array
    {
        return [
            ...$this->pairKey(),
            'kind' => (string) $this->validated('kind'),
            'mutual' => $this->boolean('mutual'),
        ];
    }

    /**
     * Read from input: the after() hook runs before validated() is final.
     *
     * @return array{source_type_id: int, target_type_id: int, scope: string}
     */
    private function pairKey(): array
    {
        /** @var InstitutionType $type */
        $type = $this->route('type');
        $other = (int) $this->input('other_type_id');
        $outgoing = $this->input('direction') === 'outgoing';

        return [
            'source_type_id' => $outgoing ? $type->id : $other,
            'target_type_id' => $outgoing ? $other : $type->id,
            'scope' => (string) $this->input('scope', InstitutionTypeLinkScope::SameTenant->value),
        ];
    }
}
