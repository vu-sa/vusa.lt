<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Media\AddImageMedia;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\StorePendingUploadRequest;
use App\Models\PendingUpload;
use App\Support\Media\ImageData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PendingUploadApiController extends ApiController
{
    public function store(StorePendingUploadRequest $request, AddImageMedia $addImage): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('image');

        $media = DB::transaction(fn () => $addImage->execute(
            PendingUpload::create(['user_id' => $request->user()->id]),
            $file,
            PendingUpload::COLLECTION,
        ));

        return $this->jsonCreated(ImageData::fromMedia($media));
    }
}
