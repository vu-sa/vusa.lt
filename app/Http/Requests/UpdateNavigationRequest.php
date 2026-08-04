<?php

namespace App\Http\Requests;

class UpdateNavigationRequest extends NavigationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $navigation = $this->route('navigation');

        return $this->user()->can('update', $navigation);
    }
}
