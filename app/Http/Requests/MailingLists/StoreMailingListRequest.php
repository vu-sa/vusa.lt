<?php

namespace App\Http\Requests\MailingLists;

use App\Models\MailingList;
use App\Services\Mailman\MailmanClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class StoreMailingListRequest extends MailingListRequest
{
    #[\Override]
    protected string $tenantScopePermission = 'mailingLists.create.padalinys';

    public function authorize(): bool
    {
        return $this->user()->can('create', MailingList::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            // Mailman's own rule for a list's local part, kept lowercase so addresses compare cleanly.
            'local_part' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
        ];
    }

    public function address(): string
    {
        return $this->validated('local_part').'@'.app(MailmanClient::class)->domain();
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('local_part')) {
                    return;
                }

                if (MailingList::query()->where('address', $this->address())->exists()) {
                    $validator->errors()->add('local_part', __('validation.unique', ['attribute' => __('mailing_lists.fields.address')]));
                }
            },
        ];
    }
}
