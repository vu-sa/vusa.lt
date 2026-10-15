<template>
  <NativeSelect
    :model-value="modelValue ?? ''"
    :options
    :placeholder="`-- ${$t('rich-content.topic_alias_none')} --`"
    placeholder-selectable
    @update:model-value="onChange"
  />
</template>

<script setup lang="ts">
/**
 * Topic picker for `topicAlias` fields (link-list's news source, news block), sourced from
 * the globally shared `tags` prop. Only `is_topic` tags are offered — others would produce
 * empty filters (see `Tag::scopeTopics()`).
 */
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { NativeSelect } from '@/Components/ui/native-select';

const modelValue = defineModel<string | undefined>();

const page = usePage();
const options = computed(() => (page.props.tags ?? [])
  .filter(tag => tag.is_topic && !!tag.alias)
  .map(tag => ({ value: tag.alias!, label: tag.name })));

function onChange(value: string | number | null | undefined): void {
  modelValue.value = value === '' || value == null ? undefined : String(value);
}
</script>
