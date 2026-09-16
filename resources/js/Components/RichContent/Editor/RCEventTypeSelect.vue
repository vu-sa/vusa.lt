<template>
  <Select :model-value="modelValue || NONE" @update:model-value="onChange">
    <SelectTrigger>
      <SelectValue />
    </SelectTrigger>
    <SelectContent>
      <SelectItem :value="NONE">
        -- {{ $t('rich-content.event_type_none') }} --
      </SelectItem>
      <SelectItem v-for="eventType in eventTypes" :key="eventType.id" :value="eventType.slug">
        {{ eventType.name }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>

<script setup lang="ts">
/**
 * Event type picker for `eventTypeSlug` fields (event-list, calendar) — one shared
 * component instead of a free-text slug input in each, sourced from the `eventTypes`
 * prop shared globally via `HandleInertiaRequests` (a handful of rows repo-wide — not
 * worth a dedicated fetch, see `QuickLinkController`'s identical rationale for the
 * topic picker on link-target forms).
 */
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

// Sentinel for "no event type" — shadcn's Select rejects an empty-string item value.
const NONE = '__none__';

const modelValue = defineModel<string | undefined>();

const page = usePage();
const eventTypes = computed(() => page.props.eventTypes ?? []);

function onChange(value: unknown): void {
  modelValue.value = value === NONE ? undefined : String(value);
}
</script>
