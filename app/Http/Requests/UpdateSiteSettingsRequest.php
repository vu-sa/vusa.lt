<?php

namespace App\Http\Requests;

use App\Settings\SettingsSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    public function rules(): array
    {
        return [
            // A page may only fill the slot of its own language.
            'privacy_page_id_lt' => ['nullable', 'string', $this->privacyPageRule('lt')],
            'privacy_page_id_en' => ['nullable', 'string', $this->privacyPageRule('en')],
        ];
    }

    private function privacyPageRule(string $lang): Exists
    {
        return Rule::exists('pages', 'id')
            ->where('lang', $lang)
            ->whereNull('deleted_at');
    }
}
