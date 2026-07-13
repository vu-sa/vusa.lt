<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Models\Institution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class ReorderDutiesRequest extends FormRequest
{
    /**
     * Institutions owning the duties being reordered, resolved once for both
     * authorization and the controller.
     *
     * @var Collection<int, Institution>|null
     */
    private ?Collection $resolvedInstitutions = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Reordering an institution's duties is gated by the ability to update that
     * institution. All duties supplied by the institution form belong to a
     * single institution, but distinct owners are authorized defensively.
     */
    public function authorize(): bool
    {
        $institutions = $this->ownerInstitutions();

        if ($institutions->isEmpty()) {
            return false;
        }

        return $institutions->every(fn ($institution) => $this->user()->can('update', $institution));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'duties' => ['required', 'array'],
            'duties.*.id' => ['required', 'exists:duties,id'],
            'duties.*.order' => ['required', 'integer'],
        ];
    }

    /**
     * Resolve the distinct institutions owning the supplied duties.
     *
     * @return Collection<int, Institution>
     */
    public function ownerInstitutions(): Collection
    {
        if ($this->resolvedInstitutions !== null) {
            return $this->resolvedInstitutions;
        }

        $dutyIds = collect($this->input('duties', []))
            ->pluck('id')
            ->filter()
            ->values();

        if ($dutyIds->isEmpty()) {
            return $this->resolvedInstitutions = new Collection;
        }

        return $this->resolvedInstitutions = Duty::whereIn('id', $dutyIds)
            ->with('institution')
            ->get()
            ->pluck('institution')
            ->filter()
            ->unique('id')
            ->values();
    }
}
