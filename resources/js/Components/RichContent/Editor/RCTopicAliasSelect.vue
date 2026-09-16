<template>
  <Select :model-value="modelValue || NONE" @update:model-value="onChange">
    <SelectTrigger>
      <SelectValue />
    </SelectTrigger>
    <SelectContent>
      <SelectItem :value="NONE">
        -- {{ $t('rich-content.topic_alias_none') }} --
      </SelectItem>
      <SelectItem v-for="topic in topics" :key="topic.id" :value="topic.alias!">
        {{ topic.name }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
/**
 * Topic picker for `topicAlias` fields (link-list's news source, news block) — one
 * shared component sourced from the `tags` prop shared globally via
 * `HandleInertiaRequests` (see `QuickLinkController`'s identical "not worth a search
 * endpoint" rationale for the topic picker on link-target forms).
 *
 * Only `is_topic` tags are offered — the rest stay descriptive-only labels and would
 * produce empty filters if picked here (see `Tag::scopeTopics()`).
 */
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

// Sentinel for "no topic" — shadcn's Select rejects an empty-string item value.
const NONE = '__none__';

const modelValue = defineModel<string | undefined>();

const page = usePage();
const topics = computed(() => (page.props.tags ?? []).filter(tag => tag.is_topic && !!tag.alias));

function onChange(value: unknown): void {
  modelValue.value = value === NONE ? undefined : String(value);
}
</script>
