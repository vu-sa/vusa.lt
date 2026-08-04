<?php

namespace App\Http\Requests;

use App\Models\Navigation;

class StoreNavigationRequest extends NavigationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Navigation::class);
    }
}
