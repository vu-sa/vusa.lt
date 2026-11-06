<?php

namespace App\Http\Requests\Meetings;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Uri;
use Throwable;

class DestroyMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('meeting')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The record page sends where to land after the meeting is gone; anything off this host is an open redirect.
            'redirect_to' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                if (! $this->isLocalUrl((string) $value)) {
                    $fail(__('validation.url', ['attribute' => $attribute]));
                }
            }],
        ];
    }

    private function isLocalUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\');
        }

        try {
            $uri = Uri::of($url);
        } catch (Throwable) {
            return false;
        }

        return in_array($uri->scheme(), ['http', 'https'], true) && $uri->host() === $this->getHost();
    }
}
