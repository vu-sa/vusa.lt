<?php

namespace App\Http\Requests;

use App\Enums\CalendarHeroStyleEnum;
use App\Http\Requests\Concerns\HasImageValidation;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Calendar;
use App\Services\PublicUrlService;
use App\Support\LocalizedRouteSlugs;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class CalendarRequest extends FormRequest
{
    use HasImageValidation;
    use ValidatesTenantScope;

    /**
     * The permission whose tenant scope constrains `tenant_id`. Store and Update override it
     * so each uses its own scope.
     */
    protected string $tenantScopePermission = 'calendars.update.padalinys';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title.lt' => 'required|string',
            'title.en' => 'nullable|string',
            'permalink.lt' => [
                'required', 'string', 'max:255',
                fn (string $attribute, mixed $value, Closure $fail) => $this->assertPermalinkAvailable('lt', (string) $value, $fail),
            ],
            'permalink.en' => [
                'nullable', 'string', 'max:255',
                fn (string $attribute, mixed $value, Closure $fail) => $this->assertPermalinkAvailable('en', (string) $value, $fail),
            ],
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'location.lt' => 'nullable|string',
            'location.en' => 'nullable|string',
            'is_remote' => 'boolean',
            'organizer.lt' => 'nullable|string',
            'organizer.en' => 'nullable|string',
            'cto_url.lt' => 'nullable|url',
            'cto_url.en' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'video_url' => 'nullable',
            'main_image_focal_point' => 'nullable|string|max:20',
            'is_draft' => 'boolean',
            'is_all_day' => 'boolean',
            'is_international' => 'boolean',
            'hero_style' => ['nullable', Rule::enum(CalendarHeroStyleEnum::class)],
            'date' => 'required|date',
            'end_date' => 'nullable|date|after:date',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope($this->tenantScopePermission)],
        ];

        // Skip file validation during precognitive requests
        if (! $this->isPrecognitive()) {
            $rules['main_image'] = $this->singleImageRules(maxMB: 10);
            $rules['images'] = $this->imagesArrayRules(maxFiles: 20);
            $rules['images.*'] = $this->galleryImageRules(maxMB: 5);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    #[\Override]
    public function messages(): array
    {
        return $this->imageValidationMessages();
    }

    /**
     * Uniqueness is scoped to the event's own year, not global: putting the year in the route
     * already keeps the same recurring title from colliding across years, so only two events in
     * the *same* year sharing a permalink is a real conflict. No DB-level unique index backs
     * this (permalink lives inside a translatable JSON column), so this closure is the only
     * enforcement — mirrors the request-only permalink uniqueness News/Page already rely on.
     */
    private function assertPermalinkAvailable(string $locale, string $value, Closure $fail): void
    {
        $date = $this->input('date');

        if (blank($date) || blank($value)) {
            return;
        }

        $year = Carbon::parse($date)->format('Y');

        $query = Calendar::query()
            ->whereYear('date', $year)
            ->where("permalink->{$locale}", $value);

        $current = $this->route('calendar');

        if ($current instanceof Calendar) {
            $query->whereKeyNot($current);
        }

        if ($query->exists()) {
            $fail(__('validation.unique', ['attribute' => "permalink ({$locale})"]));

            return;
        }

        // Clear of the live `calendar` table for this year, but could still belong to a
        // *different* event's redirect history — see PublicUrlService::isRetiredByAnother(). This
        // event re-adopting its *own* history is fine and excluded via $current's id.
        $url = LocalizedRouteSlugs::route('calendar.show', ['year' => $year, 'permalink' => $value], $locale);
        $exceptId = $current instanceof Calendar ? $current->id : null;

        if (app(PublicUrlService::class)->isRetiredByAnother($url, (new Calendar)->getMorphClass(), $exceptId)) {
            $fail(__('validation.unique', ['attribute' => "permalink ({$locale})"]));
        }
    }
}
