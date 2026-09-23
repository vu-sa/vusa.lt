<?php

namespace App\Http\Requests\Content;

use App\Models\News;

class BulkDestroyNewsRequest extends BulkContentRequest
{
    protected function modelClass(): string
    {
        return News::class;
    }

    protected function ability(): string
    {
        return 'delete';
    }
}
