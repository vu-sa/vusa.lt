<?php

namespace App\Http\Requests;

use App\Actions\GenerateUniqueSlug;
use App\Models\News;
use App\Rules\UniqueAmongTrashed;
use Closure;

class UpdateNewsRequest extends NewsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->news);
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $publishTime = $this->input('publish_time');

        if ($publishTime !== null) {
            $this->merge([
                'publish_time' => is_string($publishTime)
                    ? strtotime($publishTime)
                    : $publishTime / 1000,
            ]);
        }
    }

    /**
     * Get the tenant the news item belongs to, so permalink uniqueness is scoped
     * to that tenant instead of checked globally.
     */
    protected function getTargetTenantId(): ?int
    {
        return $this->news->tenant_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'permalink' => [
                'required', 'string', 'max:255', // matches the `news.permalink` column width
                UniqueAmongTrashed::of('news', 'permalink')->ignore($this->news->id)->where('tenant_id', $this->getTargetTenantId()),
                fn (string $attribute, mixed $value, Closure $fail) => $this->assertPermalinkNotRetiredByAnother((string) $value, $fail),
            ],
            'image' => 'nullable|string',
            'short' => 'nullable',
            'lang' => 'required|string',
        ]);
    }

    /**
     * A permalink that's clear of the live `news` table can still belong to a *different*
     * article's redirect history — see GenerateUniqueSlug::isRetiredByAnother(). Re-adopting this
     * record's *own* history is fine and excluded via $this->news->id.
     */
    private function assertPermalinkNotRetiredByAnother(string $permalink, Closure $fail): void
    {
        $tenantId = $this->getTargetTenantId();

        if (blank($permalink) || $tenantId === null) {
            return;
        }

        if (GenerateUniqueSlug::isRetiredByAnother(News::class, $permalink, $tenantId, $this->news->id)) {
            $fail(__('validation.unique', ['attribute' => 'permalink']));
        }
    }
}
