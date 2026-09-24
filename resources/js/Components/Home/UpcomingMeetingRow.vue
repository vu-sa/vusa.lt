<template>
  <Link
    :href="route('meetings.show', meeting.id)"
    prefetch
    class="flex items-center gap-4 py-4 hover:bg-secondary/40 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
  >
    <time
      :datetime="meeting.start_time"
      class="flex size-12 shrink-0 flex-col items-center justify-center border border-border bg-card text-center"
    >
      <span class="text-lg leading-none font-semibold tabular-nums">{{ day(meeting.start_time) }}</span>
      <span class="mt-0.5 text-[11px] leading-none font-semibold uppercase text-brand">{{ formatMonthShort(new Date(meeting.start_time)) }}</span>
    </time>
    <span class="min-w-0 flex-1">
      <span class="block truncate font-bold">{{ meeting.title }}</span>
      <span class="flex items-center gap-1.5 truncate text-xs text-muted-foreground">
        <span v-if="meeting.is_followed" class="inline-flex shrink-0 items-center gap-1 text-foreground" data-slot="upcoming-meeting-followed">
          <Eye class="size-3.5" aria-hidden="true" />
          {{ $t('Seki') }}
          <span aria-hidden="true" class="text-border">·</span>
        </span>
        <span class="truncate">{{ meeting.institution_name }}</span>
      </span>
    </span>
    <span class="shrink-0 text-xs text-muted-foreground">{{ formatNearDate(meeting.start_time, { thresholdDays: 365 }) }}</span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye } from 'lucide-vue-next';

import type { HomeMeeting } from './types';

import { useDateFormatter } from '@/Composables/useDateFormatter';
import { formatMonthShort } from '@/Utils/IntlTime';

defineProps<{
  meeting: HomeMeeting;
}>();

const { formatDate, formatNearDate } = useDateFormatter();
const day = (value: string) => formatDate(value).slice(8, 10);
</script>
