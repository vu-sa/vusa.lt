<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Rules\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class ReorderDutiesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Reordering an institution's duties is gated by the ability to update that
     * institution. All duties supplied by the institution form belong to a
     * single institution, but distinct owners are authorized defensively.
     */
    public function authorize(): bool
    {
        $dutyIds = collect($this->input('duties', []))->pluck('id');

        if ($dutyIds->isEmpty() || ! $dutyIds->every(fn ($id) => is_string($id) && $id !== '')) {
            return false;
        }

        $dutyIds = $dutyIds->uniqueStrict()->values();
        $duties = Duty::whereIn('id', $dutyIds)
            ->with(['institution' => fn ($query) => $query->withTrashed()])
            ->get();

        return $duties->count() === $dutyIds->count()
            && $duties->every(fn (Duty $duty) => $duty->institution !== null && $this->user()->can('update', $duty->institution));
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
            'duties.*.id' => ['required', SoftDeleteRules::existsLive('duties')],
            'duties.*.order' => ['required', 'integer'],
        ];
    }
}
