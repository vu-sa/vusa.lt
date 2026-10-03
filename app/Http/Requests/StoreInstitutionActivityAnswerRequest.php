<?php

namespace App\Http\Requests;

use App\Enums\InstitutionActivityAnswer;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\MeetingType;
use App\Models\InstitutionActivityRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInstitutionActivityAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('meetings') && $this->filled('meeting_date')) {
            $this->merge(['meetings' => [['date' => $this->input('meeting_date'), 'type' => $this->input('meeting_type'), 'time' => $this->input('meeting_time')]]]);
        }
    }

    public function rules(): array
    {
        /** @var InstitutionActivityRequest $activityRequest */
        $activityRequest = $this->route('activityRequest');
        $allowed = [InstitutionActivityAnswer::Met->value, InstitutionActivityAnswer::NotMine->value,
            $activityRequest->campaign_type === InstitutionActivityCampaign::MissingMeetings ? InstitutionActivityAnswer::Complete->value : InstitutionActivityAnswer::NotMet->value];
        $met = $this->input('answer') === InstitutionActivityAnswer::Met->value;

        return [
            'answer' => ['required', Rule::in($allowed)],
            'meetings' => [Rule::requiredIf($met), 'nullable', 'array', 'min:1', 'max:50'],
            'meetings.*' => ['array:date,type,time'],
            'meetings.*.date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$activityRequest->period_start->toDateString(), 'before_or_equal:'.$activityRequest->periodEnd()->toDateString()],
            'meetings.*.type' => ['required', Rule::enum(MeetingType::class)],
            'meetings.*.time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('answer') !== InstitutionActivityAnswer::Met->value) {
                return;
            }
            foreach ((array) $this->input('meetings', []) as $index => $meeting) {
                if (is_array($meeting) && ($meeting['type'] ?? null) !== MeetingType::Email->value && empty($meeting['time'])) {
                    $validator->errors()->add("meetings.$index.time", __('validation.required', ['attribute' => __('activity_requests.meeting_time')]));
                }
            }
        }];
    }
}
