<?php

namespace App\Http\Requests\Content;

use App\Models\News;

/** @extends BulkContentStatusRequest<News> */
class BulkUpdateNewsStatusRequest extends BulkContentStatusRequest
{
    protected function modelClass(): string
    {
        return News::class;
    }
}
