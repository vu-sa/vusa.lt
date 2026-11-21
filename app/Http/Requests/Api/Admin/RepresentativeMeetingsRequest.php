<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Institution;
use App\Services\AtstovavimasDashboardService;
use App\Services\Institutions\InstitutionQueries;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class RepresentativeMeetingsRequest extends FormRequest
{
    private const int MAX_SPAN_MONTHS = 24;

    /** @var Collection<string, string> */
    private Collection $institutionAccess;

    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $ids = $this->input('institution_ids');
        if (! is_array($ids)) {
            return true;
        }

        $requested = collect($ids)
            ->filter(fn (mixed $id): bool => is_string($id) && Str::isUlid($id))
            ->map(fn (string $id): string => strtoupper($id));
        $own = InstitutionQueries::representativeDashboardInstitutions($user, withSummary: false);
        $related = InstitutionQueries::relatedRepresentativeDashboardInstitutions($own, withSummary: false);
        $this->institutionAccess = collect($own->modelKeys())
            ->mapWithKeys(fn (string $id) => [$id => AtstovavimasDashboardService::PROJECTION_FULL])
            ->union($related->mapWithKeys(fn (Institution $institution) => [
                $institution->id => $institution->getAttribute('authorized')
                    ? AtstovavimasDashboardService::PROJECTION_FULL
                    : AtstovavimasDashboardService::PROJECTION_RESTRICTED,
            ]))
            ->filter(fn (string $projection, string $id): bool => $requested->containsStrict(strtoupper($id)));

        $allowed = $this->institutionAccess->keys()->map(fn (string $id): string => strtoupper($id));

        return $requested->diff($allowed)->isEmpty();
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'institution_ids' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'institution_ids.*' => ['required', 'string', 'ulid', 'distinct'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['from', 'to'])) {
                return;
            }

            $from = CarbonImmutable::parse($this->input('from'));
            $to = CarbonImmutable::parse($this->input('to'));

            if ($from->diffInMonths($to) > self::MAX_SPAN_MONTHS) {
                $validator->errors()->add('to', __('The date range may not span more than :months months.', ['months' => self::MAX_SPAN_MONTHS]));
            }
        }];
    }

    /** @return Collection<string, string> */
    public function institutionAccess(): Collection
    {
        return $this->institutionAccess;
    }
}
