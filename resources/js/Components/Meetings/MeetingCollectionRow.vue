<template>
  <div
    class="relative flex items-start gap-4 px-2 py-4 sm:px-4"
    data-slot="meeting-collection-row"
    :data-meeting-id="meeting.id"
  >
    <Link
      :href="route('meetings.show', meeting.id)"
      prefetch
      data-collection-open
      class="flex min-w-0 flex-1 items-start gap-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
    >
      <time
        :datetime="startsAt.toISOString()"
        class="flex size-12 shrink-0 flex-col items-center justify-center border border-border bg-card text-center"
      >
        <span class="text-lg leading-none font-semibold tabular-nums">{{ day }}</span>
        <span class="mt-0.5 text-[11px] leading-none font-semibold uppercase tracking-wide text-muted-foreground">{{ month }}</span>
      </time>

      <span class="min-w-0 flex-1">
        <span
          class="line-clamp-2 text-base font-medium text-foreground"
          :title="meeting.title || $t('Be pavadinimo')"
        >
          {{ meeting.title || $t('Be pavadinimo') }}
        </span>
        <span v-if="institutionName" class="mt-0.5 block truncate text-sm text-muted-foreground">
          {{ institutionName }}
        </span>
        <span class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
          <span>{{ formatNearDate(startsAt) }}</span>
          <span v-if="formatLabel">{{ formatLabel }}</span>
          <span v-if="time">{{ time }}</span>
          <span v-if="meeting.tenant_shortname">{{ meeting.tenant_shortname }}</span>
          <span v-if="agendaCount !== null">{{ $t('Punktų: :count', { count: String(agendaCount) }) }}</span>
          <span v-if="pinned" class="text-status-info">{{ $t('Ką tik atnaujinta') }}</span>
        </span>
      </span>
    </Link>

    <div class="flex shrink-0 flex-col items-end gap-2">
      <StatusBadge v-if="badge" :status="badge" />
      <CollectionRowActions :actions="actions ?? defaultActions" @select="emit('select', $event)" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight } from '@lucide/vue';
import { computed } from 'vue';

import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';
import { StatusBadge } from '@/Components/Patterns';
import { meetingCompletionStatuses, type MeetingCompletionStatus } from '@/Constants/statuses';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { MeetingType } from '@/Types/MeetingType';
import { formatDate, formatNearDate, formatTime } from '@/Utils/dateTime';
import { formatMonthShort } from '@/Utils/IntlTime';

const props = defineProps<{
  meeting: MeetingSearchResult;
  actions?: AdminRecordAction[];
  /** Just changed by the user and shown ahead of an index that has not caught up. */
  pinned?: boolean;
}>();

const emit = defineEmits<{ select: [key: string] }>();
const defaultActions = computed<AdminRecordAction[]>(() => [
  { key: 'open', label: $t('Atidaryti'), icon: ArrowUpRight, role: 'open', href: route('meetings.show', props.meeting.id) },
]);

// Typesense stores start_time as a unix timestamp in seconds.
const startsAt = computed(() => new Date((props.meeting.start_time ?? 0) * 1000));

const day = computed(() => formatDate(startsAt.value).slice(8, 10));
const month = computed(() => formatMonthShort(startsAt.value));

const isEmail = computed(() => props.meeting.type === MeetingType.Email);

// Not every meeting is a posėdis (.ai/rules/lang.md): an e-mail one is a decision.
const formatLabel = computed(() => {
  if (isEmail.value) {
    return $t('Sprendimas el. paštu');
  }

  return props.meeting.type === MeetingType.Remote ? $t('Nuotolinis posėdis') : null;
});

// An e-mail meeting's time is a 23:59 deadline marker, not a start.
const time = computed(() => (isEmail.value ? null : formatTime(startsAt.value)));

const institutionName = computed(() => props.meeting.institution_name_lt || props.meeting.institution_name_en);
const agendaCount = computed(() => props.meeting.agenda_items_count ?? null);

const status = computed(() => props.meeting.completion_status as MeetingCompletionStatus | undefined);

// The healthy state gets no badge: don't paint every row (.ai/rules/css.md).
const badge = computed(() => (status.value && status.value !== 'complete' ? meetingCompletionStatuses[status.value] ?? null : null));
</script>
