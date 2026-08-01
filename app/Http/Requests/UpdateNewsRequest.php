<?php

namespace App\Http\Requests;

use App\Rules\UniqueAmongTrashed;

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
            'permalink' => ['required', 'string', UniqueAmongTrashed::of('news', 'permalink')->ignore($this->news->id)->where('tenant_id', $this->getTargetTenantId())],
            'image' => 'nullable|string',
            'short' => 'nullable',
            'lang' => 'required|string',
        ]);
    }
}
