<template>
  <ActionWindowScreen
    :title="$t('action_window.institution.title')"
    :subtitle="$t('action_window.institution.subtitle')"
  >
    <div v-if="showSearch" class="pb-3">
      <Input v-model="query" :placeholder="$t('action_window.institution.search')" />
    </div>

    <div v-if="isLoading" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full rounded-xl" />
    </div>

    <EmptyState
      v-else-if="visible.length === 0 && !canSearchAll"
      :title="emptyTitle"
      :description="emptyDescription"
    >
      <template #icon>
        <Landmark class="size-10 text-muted-foreground" />
      </template>
    </EmptyState>

    <ActionChoiceList v-else>
      <ActionChoiceButton
        v-for="institution in visible"
        :key="institution.id"
        :title="institution.name"
        :icon="statusStyle(institution).icon"
        :gradient="statusStyle(institution).gradient"
        @click="pick(institution)"
      >
        <template #description>
          {{ contextLine(institution) }}
        </template>
      </ActionChoiceButton>

      <!-- Coordinators file meetings for bodies they hold no duty in, and those never
           appear in the list above. Offered only when they may actually create one. -->
      <ActionChoiceButton
        v-if="canSearchAll"
        :title="$t('action_window.institution.other')"
        :description="$t('action_window.institution.other_description')"
        :icon="SearchIcon"
        gradient="from-indigo-500/15 to-violet-500/15 dark:from-indigo-400/12 dark:to-violet-400/12"
        @click="openSearch"
      />
    </ActionChoiceList>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  CalendarCheck,
  CalendarClock,
  CalendarOff,
  CircleAlert,
  CircleHelp,
  Landmark,
  Search as SearchIcon,
  type LucideIcon,
} from 'lucide-vue-next';

import ActionChoiceButton from '../ActionChoiceButton.vue';
import ActionChoiceList from '../ActionChoiceList.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';

import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useActionWindowData, type ActionWindowInstitution } from '@/Composables/useActionWindowData';
import { EmptyState } from '@/Components/Patterns';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';

/** Above this many institutions, scanning beats scrolling. */
const SEARCH_THRESHOLD = 8;

const { current, advance, goTo, setInstitution } = useActionWindow();
const { institutions, institutionSearch, isLoading, error, load } = useActionWindowData();
const dates = useWindowDates();

const query = ref('');

onMounted(load);

const showSearch = computed(() => institutions.value.length > SEARCH_THRESHOLD);

// Check-ins are always about a body the caller serves in, so the wider search belongs
// to the meeting flow alone.
const canSearchAll = computed(() =>
  current.value.id === 'meeting.institution' && institutionSearch.value.enabled,
);

const visible = computed(() => {
  const needle = query.value.trim().toLowerCase();
  if (!needle) {
    return institutions.value;
  }
  return institutions.value.filter(institution => institution.name.toLowerCase().includes(needle));
});

const emptyTitle = computed(() =>
  error.value ? $t('action_window.common.error') : $t('action_window.institution.empty'),
);
const emptyDescription = computed(() =>
  error.value || institutions.value.length === 0 ? '' : $t('action_window.institution.search'),
);

/**
 * Each activity status gets its own icon and tint: "overdue" and "covered by a
 * check-in" are opposite situations, and a single warning triangle for both was
 * the fastest way to make the list unreadable.
 */
const STATUS_STYLES: Record<string, { icon: LucideIcon; gradient: string }> = {
  overdue: { icon: CircleAlert, gradient: 'from-red-500/20 to-rose-500/15 dark:from-red-400/15 dark:to-rose-400/12' },
  approaching: { icon: CalendarClock, gradient: 'from-amber-500/20 to-orange-500/15 dark:from-amber-400/15 dark:to-orange-400/12' },
  no_activity: { icon: CircleHelp, gradient: 'from-zinc-500/15 to-zinc-400/15 dark:from-zinc-400/12 dark:to-zinc-300/12' },
  covered_by_check_in: { icon: CalendarOff, gradient: 'from-violet-500/15 to-purple-500/15 dark:from-violet-400/12 dark:to-purple-400/12' },
  covered_by_upcoming_meeting: { icon: CalendarCheck, gradient: 'from-sky-500/15 to-indigo-500/15 dark:from-sky-400/12 dark:to-indigo-400/12' },
  healthy: { icon: Landmark, gradient: 'from-emerald-500/15 to-teal-500/15 dark:from-emerald-400/12 dark:to-teal-400/12' },
};

const FALLBACK_STYLE = { icon: Landmark, gradient: 'from-muted to-muted' };

const statusStyle = (institution: ActionWindowInstitution) =>
  STATUS_STYLES[institution.activity_status.status] ?? FALLBACK_STYLE;

/**
 * What is already scheduled, before what is overdue: a body can hold both an upcoming
 * meeting and an active check-in, and knowing only one of them was what made the list
 * look wrong ("no meetings until October" beside a meeting next Tuesday).
 */
const contextLine = (institution: ActionWindowInstitution): string => {
  const status = institution.activity_status;
  const parts: string[] = [];

  if (status.next_meeting_at) {
    parts.push($t('action_window.institution.next_meeting', { date: dates.day(status.next_meeting_at) }));
  }

  if (status.active_check_in_until) {
    parts.push(parts.length > 0
      ? $t('action_window.institution.check_in_until_short', { date: dates.day(status.active_check_in_until) })
      : $t('action_window.institution.check_in_until', { date: dates.day(status.active_check_in_until) }));
  }

  if (parts.length > 0) {
    return parts.join(' · ');
  }

  // The date, not `effective_days_since_activity`: that counter skips vacation periods,
  // so rendering it as "N days ago" told the reader something no calendar agrees with.
  if (status.last_meeting_at) {
    return $t('action_window.institution.last_meeting', { date: dates.fullDay(status.last_meeting_at) });
  }

  return $t('action_window.institution.no_meetings_yet');
};

/**
 * Carries the return frame, so changing the institution from the review still lands
 * back on the review rather than walking the rest of the flow again.
 */
const openSearch = () => {
  goTo('meeting.institution.search', { returnTo: current.value.params?.returnTo });
};

/**
 * Pushed, not replaced: picking the wrong body is an easy mis-tap, and keeping the
 * frame is also what tells the progress dots this run has an institution step.
 */
const pick = (institution: ActionWindowInstitution) => {
  setInstitution({ id: institution.id, name: institution.name, isInternal: institution.is_internal });
  advance(current.value.id === 'checkin.institution' ? 'checkin.until' : 'meeting.type');
};
</script>
