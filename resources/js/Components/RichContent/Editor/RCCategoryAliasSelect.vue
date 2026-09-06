<template>
  <Select :model-value="modelValue || NONE" @update:model-value="onChange">
    <SelectTrigger>
      <SelectValue />
    </SelectTrigger>
    <SelectContent>
      <SelectItem :value="NONE">
        -- {{ $t('rich-content.category_alias_none') }} --
      </SelectItem>
      <SelectItem v-for="category in categories" :key="category.id" :value="category.alias!">
        {{ category.name }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
/**
 * Category picker for `categoryAlias` fields (link-list's news source, event-list,
 * calendar) — one shared component instead of a free-text alias input in each, sourced
 * from the `categories` prop shared globally via `HandleInertiaRequests` (global,
 * ~7 rows repo-wide — not worth a dedicated fetch, see `QuickLinkController`'s identical
 * rationale for the category picker on link-target forms).
 *
 * Categories without an alias can't be matched by `Calendar::scopeInCategoryAlias()` /
 * the resolvers' `categoryAlias` filter, so they're excluded rather than offered as a
 * dead-end choice.
 */
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

// Sentinel for "no category" — shadcn's Select rejects an empty-string item value.
const NONE = '__none__';

const modelValue = defineModel<string | undefined>();

const page = usePage();
const categories = computed(() => (page.props.categories ?? []).filter(category => !!category.alias));

function onChange(value: unknown): void {
  modelValue.value = value === NONE ? undefined : String(value);
}
</script>
