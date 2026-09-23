<template>
  <OverviewSection
    data-tour="meetings-card"
    :title="$t('Artimiausi posėdžiai')"
    :icon="CalendarDays"
    variant="home"
    :empty="meetings.length === 0"
    :empty-text="$t('artimiausiu metu nieko nesuplanuota')"
    :href
    :href-label="$t('Visi posėdžiai')"
  >
    <ul class="divide-y divide-border border-y border-border" data-slot="upcoming-meetings">
      <li v-for="meeting in meetings" :key="meeting.id">
        <Link
          :href="route('meetings.show', meeting.id)"
          prefetch
          class="flex items-center gap-4 px-1 py-3 hover:bg-secondary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring pointer-coarse:py-4"
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
            <span class="block truncate text-xs text-muted-foreground">
              {{ meeting.institution_name }}
            </span>
          </span>
          <span class="shrink-0 text-xs text-muted-foreground">{{ formatNearDate(meeting.start_time, { thresholdDays: 365 }) }}</span>
        </Link>
      </li>
    </ul>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarDays } from 'lucide-vue-next';

import type { HomeMeeting } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { formatMonthShort } from '@/Utils/IntlTime';

defineProps<{
  meetings: HomeMeeting[];
  href?: string;
}>();

const { formatDate, formatNearDate } = useDateFormatter();
const day = (value: string) => formatDate(value).slice(8, 10);
</script>
