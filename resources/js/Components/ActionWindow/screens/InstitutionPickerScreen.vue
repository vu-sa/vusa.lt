<template>
  <ActionWindowScreen
    :title="$t('action_window.institution.title')"
    :subtitle="$t('action_window.institution.subtitle')"
  >
    <div v-if="showSearch" class="pb-3">
      <Input v-model="query" :placeholder="$t('action_window.institution.search')" />
    </div>

    <div v-if="isLoading" class="flex flex-col gap-2">
      <Skeleton v-for="n in 3" :key="n" class="h-16 w-full" />
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
        :tone="statusStyle(institution).tone"
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
import { describeInstitutionActivity } from '@/Components/Institutions/institutionActivity';
import { EmptyState } from '@/Components/Patterns';
import type { StatusRole } from '@/Constants/statuses';
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
 * Each activity status gets its own icon and status role: "overdue" and "covered by a
 * check-in" are opposite situations, and a single warning triangle for both was the
 * fastest way to make the list unreadable. Roles follow `institutionActivityStatuses`.
 */
const STATUS_STYLES: Record<string, { icon: LucideIcon; tone: StatusRole }> = {
  overdue: { icon: CircleAlert, tone: 'danger' },
  approaching: { icon: CalendarClock, tone: 'attention' },
  no_activity: { icon: CircleHelp, tone: 'neutral' },
  covered_by_check_in: { icon: CalendarOff, tone: 'info' },
  covered_by_upcoming_meeting: { icon: CalendarCheck, tone: 'info' },
  // The healthy state is the ordinary one and carries no colour (status rule: don't paint every row).
  healthy: { icon: Landmark, tone: 'neutral' },
};

const FALLBACK_STYLE: { icon: LucideIcon; tone: StatusRole } = { icon: Landmark, tone: 'neutral' };

const statusStyle = (institution: ActionWindowInstitution) =>
  STATUS_STYLES[institution.activity_status.status] ?? FALLBACK_STYLE;

const contextLine = (institution: ActionWindowInstitution): string =>
  describeInstitutionActivity(institution.activity_status, dates);

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
