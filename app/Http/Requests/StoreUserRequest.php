<?php

namespace App\Http\Requests;

use App\Models\User;

class StoreUserRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [User::class, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'facebook_url' => 'nullable|url',
            'phone' => 'nullable|string',
            'profile_photo_path' => 'nullable|string',
            'current_duties' => 'required',
        ];
    }
}
