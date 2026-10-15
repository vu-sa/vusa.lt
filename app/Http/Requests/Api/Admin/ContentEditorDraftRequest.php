<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\News;
use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;

class ContentEditorDraftRequest extends FormRequest
{
    use ValidatesTenantScope;

    public function authorize(): bool
    {
        $kind = $this->route('kind');
        abort_unless(in_array($kind, ['pages', 'news'], true), 404);
        $identity = (string) $this->route('identity');
        abort_unless(preg_match('/^(record-[1-9][0-9]*|new-[0-9a-f-]{36})$/', $identity), 404);
        $model = $kind === 'pages' ? Page::class : News::class;

        if (str_starts_with($identity, 'record-')) {
            return $this->user()->can('update', $model::findOrFail(substr($identity, 7)));
        }

        return $this->user()->can('create', $model);
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }

        $rules = ['revision' => ['required', 'integer', 'min:0']];

        if ($this->isMethod('PUT')) {
            $rules += [
                'snapshot' => ['required', 'array:id,title,permalink,lang,tenant_id,parent_id,is_active,layout,show_table_of_contents,show_title,show_breadcrumbs,highlights,featured_image,meta_description,draft,publish_time,short,image,image_author,tags,other_lang_id,pairing_confirmation,content_version,content'],
                'snapshot.content' => ['nullable', 'array'],
                'snapshot.content.parts' => ['nullable', 'array', 'max:100'],
                'snapshot.content_version' => ['nullable', 'string', 'max:64'],
            ];
            if (str_starts_with((string) $this->route('identity'), 'new-')) {
                $rules['snapshot.tenant_id'] = ['nullable', 'integer', $this->tenantIdInAuthorizedScope($this->route('kind').'.create.padalinys')];
            }
        }

        return $rules;
    }
}
