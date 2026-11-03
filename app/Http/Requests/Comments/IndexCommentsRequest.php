<?php

namespace App\Http\Requests\Comments;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use App\Http\Requests\Concerns\NormalizesBooleanFlags;
use App\Support\Commentables;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexCommentsRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;
    use NormalizesBooleanFlags;

    public function authorize(): bool
    {
        $commentable = Commentables::resolve($this->route('commentableType'), $this->route('commentableId'));
        abort_if($commentable === null, 404, 'Commentable not found.');

        return $this->user()?->can('view', $commentable) ?? false;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanFlags('resolved');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resolved' => ['nullable', 'boolean'],
        ];
    }
}
