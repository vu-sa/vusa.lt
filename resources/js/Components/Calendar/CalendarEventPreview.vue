<template>
  <PreviewPane
    entity-type="calendar"
    :eyebrow="$t('Renginys')"
    :title="event.title"
    :href="trashed ? undefined : editHref"
    :subtitle="event.eventType"
  >
    <template #status>
      <slot name="status">
        <StatusBadge v-if="event.isDraft !== undefined" :status="event.isDraft ? contentStatuses.draft : contentStatuses.published" />
      </slot>
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="editHref">
            {{ $t('Redaguoti renginį') }}
          </Link>
        </Button>
      </slot>
    </template>

    <PreviewFacts>
      <PreviewFact :label="$t('Pradžia')" :value="event.startsAt" tabular />
      <PreviewFact v-if="event.endsAt" :label="$t('Pabaiga')" :value="event.endsAt" tabular />
      <PreviewFact v-if="event.location" :label="$t('Vieta')" :value="event.location" />
      <PreviewFact :label="$t('Padalinys')" :value="event.tenantShortname" />
    </PreviewFacts>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import type { CalendarEventPreviewModel } from './calendarEventPreview';

import { PreviewFact, PreviewFacts, PreviewPane, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { contentStatuses } from '@/Constants/statuses';

const props = defineProps<{
  event: CalendarEventPreviewModel;
  /** A trashed event has no editor to open. */
  trashed?: boolean;
}>();

const editHref = computed(() => route('calendar.edit', props.event.id));
</script>
