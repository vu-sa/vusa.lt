<?php

use App\Http\Requests\CalendarRequest;
use App\Http\Requests\StoreFileableFilesRequest;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\StoreSupportRequestRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Http\Requests\UpdateSupportRequestRequest;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

pest()->use(RefreshDatabase::class);

test('image fields accept matching raster files and refuse mismatched filenames and contents', function (string $requestClass, string $field): void {
    asUser(makeUser(Tenant::query()->first()));
    $rules = (new $requestClass)->rules()[$field];
    $data = fn (UploadedFile $file): array => ['uploads' => [['file' => $file]]];
    $validate = fn (UploadedFile $file) => Validator::make($data($file), ['uploads.*.file' => $rules]);

    expect($validate(UploadedFile::fake()->image('photo.png'))->passes())->toBeTrue()
        ->and($validate(UploadedFile::fake()->image('photo.svg')->mimeType('image/jpeg'))->passes())->toBeFalse()
        ->and($validate(UploadedFile::fake()->image('photo.html')->mimeType('image/jpeg'))->passes())->toBeFalse()
        ->and($validate(UploadedFile::fake()->createWithContent('photo.png', '<html><body>Document</body></html>')->mimeType('text/html'))->passes())->toBeFalse();
})->with([
    [StoreResourceRequest::class, 'media.*.file'],
    [UpdateResourceRequest::class, 'media.*.file'],
    [StoreSupportRequestRequest::class, 'images.*'],
    [UpdateSupportRequestRequest::class, 'images.*'],
    [CalendarRequest::class, 'images.*'],
]);

test('record attachments require document extensions and document contents', function (): void {
    $rules = (new StoreFileableFilesRequest)->rules()['files.*.file'];
    $pdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF";

    expect(Validator::make(['file' => UploadedFile::fake()->createWithContent('document.pdf', $pdf)], ['file' => $rules])->passes())->toBeTrue()
        ->and(Validator::make(['file' => UploadedFile::fake()->createWithContent('document.html', $pdf)->mimeType('application/pdf')], ['file' => $rules])->passes())->toBeFalse();
});
