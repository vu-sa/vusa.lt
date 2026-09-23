<?php

namespace App\Http\Requests\Content;

use App\Models\Page;

class BulkUpdatePageStatusRequest extends BulkContentStatusRequest
{
    protected function modelClass(): string
    {
        return Page::class;
    }
}
