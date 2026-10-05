<?php

namespace App\Http\Requests\ActivityRequests;

use App\Enums\InstitutionActivityCampaign;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Institutions a coordinator asks "Ar vyko posėdis?" about, optionally narrowed to picked people. Ones outside their reach are not
 * refused here but skipped by SendInstitutionActivityRequests, so a mixed list still sends.
 */
abstract class InstitutionActivityRequestsRequest extends FormRequest
{
    public const int MAX_INSTITUTIONS = 100;

    /** @var Collection<int, Institution>|null */
    private ?Collection $institutions = null;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', InstitutionCheckIn::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'campaign_type' => ['required', Rule::enum(InstitutionActivityCampaign::class)],
            'institution_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_INSTITUTIONS],
            'institution_ids.*' => ['string', 'distinct', 'exists:institutions,id'],
            'recipients' => ['nullable', 'array', 'max:500'],
            'recipients.*' => ['array:institution_id,user_id'],
            'recipients.*.institution_id' => ['required', 'string', Rule::in((array) $this->input('institution_ids', []))],
            'recipients.*.user_id' => ['required', 'string'],
        ];
    }

    /**
     * The picked "institution_id:user_id" pairs, or null to ask whoever each institution asks by default.
     *
     * @return list<string>|null
     */
    public function pairs(): ?array
    {
        $recipients = $this->validated('recipients');

        return is_array($recipients) ? array_values(array_map(fn (array $pair): string => $pair['institution_id'].':'.$pair['user_id'], $recipients)) : null;
    }

    /**
     * @return Collection<int, Institution>
     */
    public function institutions(): Collection
    {
        $ids = $this->input('institution_ids');

        return $this->institutions ??= Institution::query()
            ->whereIn('id', is_array($ids) ? array_slice(array_filter($ids, is_string(...)), 0, self::MAX_INSTITUTIONS) : [])
            ->with(['types', 'meetings:id,start_time', 'checkIns'])
            ->get();
    }
}
