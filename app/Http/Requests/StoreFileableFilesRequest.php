<?php

namespace App\Http\Requests;

use App\Contracts\SharepointFileableContract;
use App\Enums\AllowedFileablesEnum;
use App\Models\FileableFile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Upload one or more files into a record's SharePoint folder. Authorized against the record
 * itself: holding the SharePoint capability somewhere never reaches another tenant's meeting.
 */
class StoreFileableFilesRequest extends FormRequest
{
    private ?Model $resolvedFileable = null;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->fileable()) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1|max:10',
            'files.*.file' => 'required|file|mimes:pdf,docx,pptx,xlsx|max:51200',
            'files.*.type' => ['required', 'string', Rule::in(array_keys(FileableFile::fileTypes()))],
            'files.*.date' => 'required|date',
            'files.*.name' => ['nullable', 'string', 'max:200', 'not_regex:/[\\\\\/:*?"<>|#%]/'],
        ];
    }

    /**
     * @return Model&SharepointFileableContract
     */
    public function fileable(): Model
    {
        if ($this->resolvedFileable !== null) {
            /** @var Model&SharepointFileableContract */
            return $this->resolvedFileable;
        }

        $class = AllowedFileablesEnum::classFor((string) $this->route('type'));

        abort_if($class === null, 404);

        $fileable = $class::query()->find($this->route('id'));

        abort_unless($fileable instanceof SharepointFileableContract, 404);

        /** @var Model&SharepointFileableContract $fileable */
        return $this->resolvedFileable = $fileable;
    }
}
