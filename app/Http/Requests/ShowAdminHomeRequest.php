<?php

namespace App\Http\Requests;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pradžia can open the ActionWindow on arrival (`?window=…&institution=…`) — that is how a
 * reminder's answer buttons work (U21). A bad link must not turn the home page into an error, so
 * anything that does not resolve to something the user may open is quietly ignored.
 */
class ShowAdminHomeRequest extends FormRequest
{
    /** @var list<string> */
    public const array WINDOWS = ['meeting.create', 'check-in'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'window' => ['sometimes', 'nullable', 'string'],
            'institution' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array{flow: string, institution: array{id: string, name: string, isInternal: bool}}|null
     */
    public function actionWindowLaunch(User $user): ?array
    {
        $window = $this->validated('window');
        $institutionId = $this->validated('institution');

        if (! in_array($window, self::WINDOWS, true) || ! is_string($institutionId)) {
            return null;
        }

        $institution = Institution::query()->find($institutionId);

        if ($institution === null || ! $user->can('view', $institution)) {
            return null;
        }

        return [
            'flow' => $window,
            'institution' => [
                'id' => (string) $institution->id,
                'name' => (string) $institution->name,
                'isInternal' => $institution->governance_scope->isInternal(),
            ],
        ];
    }
}
