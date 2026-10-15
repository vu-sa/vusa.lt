<template>
  <NativeSelect
    :model-value="modelValue ?? ''"
    :options
    :placeholder="`-- ${$t('rich-content.event_type_none')} --`"
    placeholder-selectable
    @update:model-value="onChange"
  />
</template>

<script setup lang="ts">
/**
 * Event type picker for `eventTypeSlug` fields (event-list, calendar), sourced from the
 * globally shared `eventTypes` prop — a handful of rows, so no dedicated fetch.
 */
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { NativeSelect } from '@/Components/ui/native-select';

const modelValue = defineModel<string | undefined>();

const page = usePage();
const options = computed(() => (page.props.eventTypes ?? []).map(eventType => ({
  value: eventType.slug,
  label: eventType.name,
})));

function onChange(value: string | number | null | undefined): void {
  modelValue.value = value === '' || value == null ? undefined : String(value);
}
</script>
