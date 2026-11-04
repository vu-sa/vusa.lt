<?php

namespace App\Http\Requests\ActivityRequests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pages that show an activity-request history paginate it under its own key, so it can
 * page independently of anything else on the record.
 */
class ActivityRequestHistoryRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    public function authorize(): bool
    {
        if ($institution = $this->route('institution')) {
            return $this->user()?->can('viewSummary', $institution) ?? false;
        }

        if ($user = $this->route('user')) {
            return $this->user()?->can('view', $user) ?? false;
        }

        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'activity_requests_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function historyPage(): int
    {
        return (int) ($this->validated('activity_requests_page') ?? 1);
    }
}
