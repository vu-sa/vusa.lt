<?php

namespace App\Http\Requests\InstitutionLinks;

use App\Enums\InstitutionRelationKind;
use App\Models\Institution;
use App\Models\InstitutionLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Links the institution in the URL with another one, in either direction.
 */
class StoreInstitutionLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', InstitutionLink::class) ?? false;
    }

    public function rules(): array
    {
        /** @var Institution $institution */
        $institution = $this->route('institution');

        return [
            'other_institution_id' => ['required', 'string', Rule::exists('institutions', 'id')->withoutTrashed(), Rule::notIn([$institution->id])],
            'direction' => ['required', Rule::in(['outgoing', 'incoming'])],
            'kind' => ['required', Rule::enum(InstitutionRelationKind::class)],
            'mutual' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Institution $institution */
                $institution = $this->route('institution');
                $other = (string) $this->input('other_institution_id');

                $alreadyLinked = InstitutionLink::query()
                    ->where(fn ($query) => $query->where('source_institution_id', $institution->id)->where('target_institution_id', $other))
                    ->orWhere(fn ($query) => $query->where('source_institution_id', $other)->where('target_institution_id', $institution->id))
                    ->exists();

                if ($alreadyLinked) {
                    $validator->errors()->add('other_institution_id', __('Šios institucijos jau susietos. Redaguok esamą ryšį.'));
                }
            },
        ];
    }

    /**
     * @return array{source_institution_id: string, target_institution_id: string, kind: string, mutual: bool}
     */
    public function linkAttributes(): array
    {
        /** @var Institution $institution */
        $institution = $this->route('institution');
        $other = (string) $this->validated('other_institution_id');
        $outgoing = $this->validated('direction') === 'outgoing';

        return [
            'source_institution_id' => $outgoing ? $institution->id : $other,
            'target_institution_id' => $outgoing ? $other : $institution->id,
            'kind' => (string) $this->validated('kind'),
            'mutual' => $this->boolean('mutual'),
        ];
    }
}
