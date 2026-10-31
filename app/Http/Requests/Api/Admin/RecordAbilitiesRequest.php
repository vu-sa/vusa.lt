<?php

namespace App\Http\Requests\Api\Admin;

use App\Models\Document;
use App\Models\Institution;
use App\Models\MailingList;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Resource;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordAbilitiesRequest extends FormRequest
{
    /** @var array<string, class-string<Model>> */
    public const MODELS = [
        'institutions' => Institution::class,
        'meetings' => Meeting::class,
        'agenda_items' => AgendaItem::class,
        'resources' => Resource::class,
        'documents' => Document::class,
        'users' => User::class,
        'responsibilities' => Role::class,
        'mailingLists' => MailingList::class,
        'supportRequests' => SupportRequest::class,
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(self::MODELS))],
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) && ! is_int($value)) {
                    $fail(__('validation.string', ['attribute' => $attribute]));
                }
            }],
        ];
    }
}
