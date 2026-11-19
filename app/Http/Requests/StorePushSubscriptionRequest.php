<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Self-scoped: the subscription is always written against the acting user.
 */
class StorePushSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'endpoint' => 'required|url',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
            'contentEncoding' => 'nullable|string',
            'deviceName' => 'nullable|string|max:255',
        ];
    }
}
