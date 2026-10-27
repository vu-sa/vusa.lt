<?php

namespace App\Http\Requests;

use App\Actions\Duties\FoldPredecessorIntoRename;
use App\Http\Controllers\Admin\DutySuccessionController;
use App\Models\Duty;
use App\Models\Pivots\DutySuccession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/** Folding deletes the predecessor and moves its terms, so the actor must be able to edit both duties. */
class FoldDutyRenameRequest extends FormRequest
{
    private ?DutySuccession $link = null;

    public function authorize(): bool
    {
        $predecessor = $this->link()->predecessor;

        return $predecessor !== null
            && $this->user()->can('update', $this->duty())
            && $this->user()->can('update', $predecessor);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    public function link(): DutySuccession
    {
        return $this->link ??= DutySuccessionController::linkOf($this->duty(), (string) $this->route('succession'));
    }

    protected function passedValidation(): void
    {
        if (! FoldPredecessorIntoRename::isEligible($this->link())) {
            throw ValidationException::withMessages([
                'succession' => __('Pervadinimu galima paversti tik vienos pareigybės ryšį su viena pareigybe toje pačioje institucijoje.'),
            ]);
        }
    }

    private function duty(): Duty
    {
        /** @var Duty $duty */
        $duty = $this->route('duty');

        return $duty;
    }
}
