<template>
  <Link
    :href="route('meetings.show', meeting.id)"
    prefetch
    :class="cn(ROW_LINK_CLASS, 'items-center gap-4')"
  >
    <time
      :datetime="meeting.start_time"
      class="flex size-12 shrink-0 flex-col items-center justify-center rounded-md border border-border bg-card text-center"
    >
      <span class="mb-0.5 text-[11px] leading-none font-semibold uppercase text-brand">{{ formatMonthAbbr(meeting.start_time) }}</span>
      <span class="text-lg leading-none font-semibold tabular-nums">{{ day(meeting.start_time) }}</span>
    </time>
    <span class="min-w-0 flex-1">
      <span class="block truncate font-semibold">{{ meeting.title }}</span>
      <span class="flex items-center gap-1.5 truncate text-xs text-muted-foreground">
        <span v-if="meeting.is_followed" class="inline-flex shrink-0 items-center gap-1 text-foreground" data-slot="upcoming-meeting-followed">
          <Eye class="size-3.5" aria-hidden="true" />
          {{ $t('Seki') }}
          <span aria-hidden="true" class="text-border">·</span>
        </span>
        <span class="truncate">{{ meeting.institution_name }}</span>
        <StatusText v-if="meeting.institution_is_active === false" :status="inactiveInstitutionStatus" class="shrink-0" />
      </span>
    </span>
    <span class="shrink-0 text-xs text-muted-foreground">{{ formatNearDate(meeting.start_time, { thresholdDays: 365 }) }}</span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye } from '@lucide/vue';

import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import { StatusText } from '@/Components/Patterns';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { inactiveInstitutionStatus } from '@/Constants/statusIcons';
import { cn } from '@/Utils/Shadcn/utils';

import type { HomeMeeting } from './types';

defineProps<{
  meeting: HomeMeeting;
}>();

const { formatDateWith, formatMonthAbbr, formatNearDate } = useDateFormatter();
const day = (value: string) => formatDateWith(value, { day: '2-digit' });
</script>
