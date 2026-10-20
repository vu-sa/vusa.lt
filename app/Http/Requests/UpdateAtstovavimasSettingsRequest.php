<?php

namespace App\Http\Requests;

use App\Services\RepMetricReportService;
use App\Settings\SettingsSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAtstovavimasSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    public function rules(): array
    {
        return [
            'baseline_cadence_id' => ['nullable', 'string', Rule::in(app(RepMetricReportService::class)->baselineOptions()->pluck('id')->all())],
            'student_rep_root_type_id' => 'nullable|integer|exists:institution_types,id',
        ];
    }
}
