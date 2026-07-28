<?php

namespace App\Http\Requests\Concerns;

use App\Enums\ContentPartEnum;
use App\Rules\SoftDeleteRules;
use Illuminate\Validation\Rule;

/**
 * Shared `content.parts.*` validation rules for StorePageRequest, UpdatePageRequest and
 * NewsRequest — before this existed, the same six rules were hand-copied across all
 * three, and had already drifted once (the update-branch of ContentService::
 * updateContentParts only validated on create).
 *
 * The dynamic-block option rules below are deliberately a *permissive union*, not a
 * strict per-type shape: Laravel has no first-class "validate this key only when
 * `type` is X" for a `content.parts.*` wildcard without `Rule::forEach()` (untested
 * in this codebase), and the real guard is the resolver's own clamping
 * (LinkListResolver / EventListResolver), which runs on every read regardless of
 * what passed validation at write time — including rows written before these rules
 * existed, or through `ContentService::updateContentParts()` directly. Validation
 * here exists to reject obviously-wrong input early, not as the security boundary.
 */
trait ValidatesContentParts
{
    /**
     * @return array<string, mixed>
     */
    protected function contentPartRules(): array
    {
        return [
            'content.parts' => 'required|array',
            'content.parts.*.id' => 'nullable|integer',
            'content.parts.*.type' => ['required', 'string', Rule::in(ContentPartEnum::toArray())],
            'content.parts.*.json_content' => 'present',
            'content.parts.*.order' => 'nullable|integer',

            'content.parts.*.options' => 'nullable|array',
            'content.parts.*.options.width' => ['nullable', 'string', Rule::in(['prose', 'content', 'wide', 'full'])],
            // Shared RCSection chrome (Editor/RCSectionOptions.vue) — any type that
            // renders through RCSection may carry these.
            'content.parts.*.options.title' => 'nullable|string|max:255',
            'content.parts.*.options.subtitle' => 'nullable|string|max:255',
            'content.parts.*.options.background' => ['nullable', 'string', Rule::in(['none', 'muted', 'contrast', 'gradient'])],
            'content.parts.*.options.padding' => ['nullable', 'string', Rule::in(['none', 'sm', 'md', 'lg'])],
            'content.parts.*.options.rounded' => ['nullable', 'string', Rule::in(['none', 'sm', 'md', 'lg'])],

            // section
            'content.parts.*.options.inner' => ['nullable', 'string', Rule::in(['prose', 'content', 'wide', 'full'])],
            'content.parts.*.options.wraps' => ['nullable', 'string', Rule::in(['following', 'none'])],

            // content-grid
            'content.parts.*.options.verticalAlign' => ['nullable', 'string', Rule::in(['stretch', 'start', 'center', 'end'])],

            // link-list
            'content.parts.*.options.source' => ['nullable', 'string', Rule::in(['news', 'pages', 'manual'])],
            'content.parts.*.options.mode' => ['nullable', 'string', Rule::in(['latest', 'specific', 'upcoming', 'range', 'year'])],
            'content.parts.*.options.categoryAlias' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'content.parts.*.options.tenantScope' => 'nullable', // 'current' | 'all' | int[] — shape-checked in the resolver
            'content.parts.*.options.newsIds' => 'nullable|array|max:12',
            'content.parts.*.options.newsIds.*' => 'integer',
            'content.parts.*.options.pageIds' => 'nullable|array|max:12',
            'content.parts.*.options.pageIds.*' => 'integer',
            'content.parts.*.options.limit' => 'nullable|integer|min:1|max:24',
            'content.parts.*.options.style' => ['nullable', 'string', Rule::in(['photo', 'compact', 'cards', 'list'])],
            'content.parts.*.options.emptyMessage' => 'nullable|string|max:255',
            'content.parts.*.json_content.links' => 'nullable|array|max:12',
            'content.parts.*.json_content.links.*.title' => 'nullable|string|max:255',
            'content.parts.*.json_content.links.*.url' => 'nullable|url:http,https|max:2048',
            'content.parts.*.json_content.links.*.imageUrl' => 'nullable|string|max:2048',
            // Editor-only bookkeeping so CollectionSelectDialog can re-open with the
            // currently-pinned items pre-checked (see LinkListEditor.vue); never read
            // by LinkListResolver, which re-fetches the live records by id.
            'content.parts.*.json_content.pinnedNews' => 'nullable|array|max:12',
            'content.parts.*.json_content.pinnedNews.*.id' => 'integer',
            'content.parts.*.json_content.pinnedNews.*.title' => 'nullable|string|max:255',
            'content.parts.*.json_content.pinnedPages' => 'nullable|array|max:12',
            'content.parts.*.json_content.pinnedPages.*.id' => 'integer',
            'content.parts.*.json_content.pinnedPages.*.title' => 'nullable|string|max:255',

            // event-list
            'content.parts.*.options.groupBy' => ['nullable', 'string', Rule::in(['none', 'tenant'])],
            'content.parts.*.options.year' => 'nullable|integer|min:2000|max:'.((int) date('Y') + 2),
            'content.parts.*.options.dateFrom' => 'nullable|date_format:Y-m-d',
            'content.parts.*.options.dateTo' => 'nullable|date_format:Y-m-d|after_or_equal:content.parts.*.options.dateFrom',
            'content.parts.*.options.tenantLabelPrefix' => 'nullable|string|max:32',
            'content.parts.*.options.tenantLabelStyle' => ['nullable', 'string', Rule::in(['full', 'faculty'])],

            // person-quote
            'content.parts.*.options.align' => ['nullable', 'string', Rule::in(['start', 'center'])],
            'content.parts.*.options.showAvatar' => 'nullable|boolean',
            'content.parts.*.json_content.snapshot.userId' => ['nullable', 'integer', SoftDeleteRules::existsLive('users')],
            'content.parts.*.json_content.snapshot.name' => 'nullable|string|max:255',
            'content.parts.*.json_content.snapshot.photoUrl' => 'nullable|string|max:2048',
            'content.parts.*.json_content.snapshot.attribution' => 'nullable|string|max:255',

            // spacer
            'content.parts.*.options.size' => ['nullable', 'string', Rule::in(['xs', 'sm', 'md', 'lg', 'xl', '2xl'])],
        ];
    }
}
