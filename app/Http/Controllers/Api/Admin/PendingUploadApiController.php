<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Media\AddImageMedia;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\StorePendingUploadRequest;
use App\Models\PendingUpload;
use App\Support\Media\ImageData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class PendingUploadApiController extends ApiController
{
    public function store(StorePendingUploadRequest $request, AddImageMedia $addImage): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('image');

        $upload = PendingUpload::create(['user_id' => $request->user()->id]);

        return $this->jsonCreated(ImageData::fromMedia($addImage->execute($upload, $file, PendingUpload::COLLECTION)));
    }
}
