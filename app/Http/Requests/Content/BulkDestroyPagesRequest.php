<?php

namespace App\Http\Requests\Content;

use App\Models\Page;

class BulkDestroyPagesRequest extends BulkContentRequest
{
    protected function modelClass(): string
    {
        return Page::class;
    }

    protected function ability(): string
    {
        return 'delete';
    }
}
