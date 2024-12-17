<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateProgrammeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $days = $this->input('days');

        foreach ($days as &$day) {
            // if is null or is string, continue
            if ($day['start_time'] === null) {
                continue;
            } elseif (is_string($day['start_time'])) {
                $day['start_time'] = Carbon::parse($day['start_time'])->setTimezone('Europe/Vilnius')->toDateTimeString();
            } else {
                $day['start_time'] = Carbon::parse($day['start_time'] / 1000)->setTimezone('Europe/Vilnius')->toDateTimeString();
            }
        }

        $this->merge([
            'days' => $days,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'days' => 'required|array',
            'days.*.id' => 'required',
            'days.*.title' => 'required|array',
            'days.*.start_time' => 'required|date',
            'days.*.elements' => 'required|array',
            'days.*.elements.*.id' => 'required',
            'days.*.elements.*.title' => 'required|array',
            'days.*.elements.*.description' => 'nullable|array',
            'days.*.elements.*.duration' => 'required|integer|min:1',
            'days.*.elements.*.instructor' => 'nullable|string',
            'days.*.elements.*.start_time' => 'nullable|date_format:H:i:s',
            'days.*.elements.*.type' => 'required|string|in:section,part',
            'days.*.elements.*.blocks' => 'nullable|array',
            'days.*.elements.*.blocks.*.id' => 'required',
            'days.*.elements.*.blocks.*.title' => 'required|array',
            'days.*.elements.*.blocks.*.description' => 'nullable|array',
            'days.*.elements.*.blocks.*.parts' => 'nullable|array',
            'days.*.elements.*.blocks.*.parts.*.id' => 'required',
            'days.*.elements.*.blocks.*.parts.*.title' => 'required|array',
            'days.*.elements.*.blocks.*.parts.*.description' => 'nullable|array',
            'days.*.elements.*.blocks.*.parts.*.duration' => 'required|integer|min:1',
            'days.*.elements.*.blocks.*.parts.*.start_time' => 'nullable|date_format:H:i:s',
            'days.*.elements.*.blocks.*.parts.*.instructor' => 'nullable|string',
        ];
    }
}
