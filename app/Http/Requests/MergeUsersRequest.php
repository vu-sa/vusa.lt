<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class MergeUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', User::find($this->kept_user_id)) &&
            $this->user()->can('delete', User::find($this->merged_user_id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kept_user_id' => 'required|ulid|exists:users,id',
            'merged_user_id' => 'required|ulid|different:kept_user_id|exists:users,id',
        ];
    }
}
