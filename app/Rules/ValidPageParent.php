<?php

namespace App\Rules;

use App\Models\Page;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A page's parent must share its language (pages are one row per language, so an LT
 * page cannot hang off an EN one), belong to the same tenant, not be the page itself
 * or one of its own descendants (a cycle), and keep the chain to at most 3 levels.
 *
 * A missing/soft-deleted parent is left to the accompanying `exists` rule so the
 * error points at the real problem instead of one of these.
 */
class ValidPageParent implements ValidationRule
{
    private const int MAX_DEPTH = 3;

    public function __construct(
        private readonly string $lang,
        private readonly ?int $tenantId,
        private readonly ?int $excludeDescendantsOf = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $this->lang === '' || $this->tenantId === null) {
            return;
        }

        $parent = Page::query()->withTrashed()->find($value);

        if ($parent === null) {
            return;
        }

        if ($this->excludeDescendantsOf !== null && (int) $value === $this->excludeDescendantsOf) {
            $fail(__('validation.page_parent_self'));

            return;
        }

        if ($parent->lang !== $this->lang) {
            $fail(__('validation.page_parent_lang_mismatch'));

            return;
        }

        if ($parent->tenant_id !== $this->tenantId) {
            $fail(__('validation.page_parent_tenant_mismatch'));

            return;
        }

        if ($this->excludeDescendantsOf !== null) {
            $page = Page::query()->find($this->excludeDescendantsOf);

            if ($page !== null && in_array((int) $value, $page->descendantIds(), true)) {
                $fail(__('validation.page_parent_cycle'));

                return;
            }
        }

        // Depth of the parent itself, root counted as 1 — a new child sits one level
        // deeper, so the chain is capped once the parent already sits at MAX_DEPTH.
        $depth = 1;
        $ancestor = $parent;

        for ($i = 0; $i < self::MAX_DEPTH && $ancestor->parent_id !== null; $i++) {
            $ancestor = Page::query()->withTrashed()->find($ancestor->parent_id);

            if ($ancestor === null) {
                break;
            }

            $depth++;
        }

        if ($depth >= self::MAX_DEPTH) {
            $fail(__('validation.page_parent_too_deep'));
        }
    }
}
