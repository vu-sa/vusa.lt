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

    <EmptyState v-else-if="visible.length === 0" :title="emptyTitle" :description="emptyDescription">
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

const { current, advance, setInstitution } = useActionWindow();
const { institutions, isLoading, error, load } = useActionWindowData();
const dates = useWindowDates();

const query = ref('');

onMounted(load);

const showSearch = computed(() => institutions.value.length > SEARCH_THRESHOLD);

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

const contextLine = (institution: ActionWindowInstitution): string => {
  const status = institution.activity_status;

  if (status.active_check_in_until) {
    return $t('action_window.institution.check_in_until', { date: dates.day(status.active_check_in_until) });
  }

  if (status.last_meeting_at && status.effective_days_since_activity !== null) {
    return $t('action_window.institution.last_meeting', { days: String(status.effective_days_since_activity) });
  }

  return $t('action_window.institution.no_meetings_yet');
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
