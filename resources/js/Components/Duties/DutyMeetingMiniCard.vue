<template>
  <Link
    :href="route('meetings.show', meeting.id)"
    :class="['flex items-center gap-2.5 rounded-lg border border-border bg-card px-2.5 py-2', interactiveCardClass]"
  >
    <!-- Date badge -->
    <div class="flex size-10 shrink-0 flex-col items-center justify-center rounded-lg bg-muted leading-none">
      <span class="text-[10px] font-medium uppercase text-muted-foreground">
        {{ monthLabel }}
      </span>
      <span class="text-base font-bold text-foreground">
        {{ dayLabel }}
      </span>
    </div>

    <div class="min-w-0 flex-1 leading-tight">
      <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
        {{ label }}
      </p>
      <p class="truncate text-sm font-medium text-foreground">
        {{ title }}
      </p>
    </div>

    <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground" />
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight } from 'lucide-vue-next';

import { formatStaticTime, formatMonthShort } from '@/Utils/IntlTime';
import { interactiveCardClass } from '@/Utils/interactiveCard';

export interface MiniMeeting {
  id: string | number;
  title?: string | null;
  start_time: string;
}

const props = defineProps<{
  meeting: MiniMeeting;
  label: string;
}>();

const monthLabel = computed(() => formatMonthShort(new Date(props.meeting.start_time)));
const dayLabel = computed(() => formatStaticTime(new Date(props.meeting.start_time), { day: 'numeric' }));

const title = computed(() => {
  if (props.meeting.title && props.meeting.title.trim() !== '') {
    return props.meeting.title;
  }
  return `${formatStaticTime(new Date(props.meeting.start_time), { year: 'numeric', month: 'long', day: 'numeric' })} ${$t('posėdis')}`;
});
</script>
