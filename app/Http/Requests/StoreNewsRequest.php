<?php

namespace App\Http\Requests;

use App\Models\News;
use Illuminate\Support\Carbon;

class StoreNewsRequest extends NewsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', News::class);
    }

    protected function prepareForValidation()
    {
        $publishTime = $this->input('publish_time');

        if ($publishTime !== null) {
            $this->merge([
                'publish_time' => is_string($publishTime)
                    ? Carbon::createFromTimestamp(strtotime($publishTime), 'Europe/Vilnius')
                    : Carbon::createFromTimestampMs($publishTime, 'Europe/Vilnius'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'permalink' => 'required|unique:news,permalink',
            'image' => 'required',
            'short' => 'required',
        ]);
    }
}
