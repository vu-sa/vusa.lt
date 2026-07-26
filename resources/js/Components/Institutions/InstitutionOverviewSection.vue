<template>
  <div class="space-y-6">
    <div
      :class="[
        'flex flex-col gap-3 rounded-lg border px-4 py-3 sm:flex-row sm:items-center sm:justify-between',
        activityPanelClass,
      ]"
    >
      <div class="flex items-start gap-3">
        <component :is="activityIcon" class="mt-0.5 h-5 w-5 shrink-0" />
        <div class="space-y-1">
          <p class="font-medium">
            {{ activityLabel }}
          </p>
          <p v-if="activityStatus.effective_days_since_activity !== null" class="text-sm opacity-80">
            {{ activityStatus.effective_days_since_activity }} {{ $t('d.') }} /
            {{ activityStatus.periodicity_days }} {{ $t('d.') }}
          </p>
          <p v-else-if="activityStatus.next_meeting_at" class="text-sm opacity-80">
            {{ formatLastMeetingDate(activityStatus.next_meeting_at) }}
          </p>
          <p v-else-if="activityStatus.active_check_in_until" class="text-sm opacity-80">
            {{ $t('iki') }} {{ formatLastMeetingDate(activityStatus.active_check_in_until) }}
          </p>
        </div>
      </div>
      <div v-if="activityStatus.requires_action" class="flex flex-wrap gap-2">
        <Button size="sm" class="gap-2" @click="$emit('schedule-meeting')">
          <CalendarIcon class="h-4 w-4" />
          {{ $t('tasks.periodicity_gap.schedule_meeting') }}
        </Button>
        <Button variant="outline" size="sm" class="gap-2" @click="$emit('report-activity')">
          <Clock class="h-4 w-4" />
          {{ $t('tasks.periodicity_gap.report_no_meeting') }}
        </Button>
      </div>
    </div>

    <div
      v-if="lastMeeting"
      class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400"
    >
      <CalendarIcon class="h-4 w-4" />
      {{ $t('Paskutinis susitikimas') }}:
      <span class="font-medium text-zinc-900 dark:text-zinc-100">
        {{ formatLastMeetingDate(lastMeeting.start_time) }}
      </span>
    </div>

    <!-- About -->
    <SectionCard v-if="description" :title="$t('Apie')" :icon="Info">
      <div class="text-sm leading-relaxed text-muted-foreground" v-html="description" />
    </SectionCard>

    <!-- Members + Meetings -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <SectionCard
        :title="$t('Nariai')"
        :icon="Users"
        :count="`${filledPositions} / ${totalPositions}`"
        :empty="members.length === 0"
        @action="$emit('navigate-tab', 'duties')"
      >
        <template #action>
          <div class="flex items-center gap-2">
            <Button
              v-if="canEditMembers"
              variant="outline"
              size="sm"
              class="gap-2"
              @click="$emit('add-member')"
            >
              <UserPlus class="h-4 w-4" />
              {{ $t('Pridėti') }}
            </Button>
            <button
              type="button"
              class="inline-flex shrink-0 items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
              @click="$emit('navigate-tab', 'duties')"
            >
              {{ $t('Visi nariai') }}
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </template>

        <div class="space-y-1">
          <button
            v-for="member in previewMembers"
            :key="member.id"
            type="button"
            :class="[
              'flex w-full items-center gap-3 rounded-md border border-transparent px-2 py-2 text-left',
              interactiveCardClass,
              'focus:outline-none focus:ring-2 focus:ring-primary/50',
            ]"
            @click="$emit('view-profile', member)"
          >
            <UserPopover :user="member" :size="32" :clickable="false" />
            <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">{{ member.name }}</span>
            <Badge v-if="roleForMember(member)" variant="secondary" class="shrink-0 text-xs">
              {{ roleForMember(member) }}
            </Badge>
          </button>

          <p v-if="members.length > previewMembers.length" class="px-2 pt-1 text-xs text-muted-foreground">
            {{ $t('ir dar :count', { count: String(members.length - previewMembers.length) }) }}
          </p>
        </div>

        <!-- Capacity Warning -->
        <div
          v-if="showCapacityWarning"
          class="mt-3 flex items-center gap-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300"
        >
          <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
          {{ $t('Viršytas narių limitas') }}
        </div>

        <template #empty>
          <div class="py-8 text-center">
            <Users class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
              {{ $t('Nėra narių') }}
            </p>
          </div>
        </template>
      </SectionCard>

      <InstitutionMeetingsPreview
        v-if="institution.meetings && institution.meetings.length > 0"
        :meetings="recentMeetings"
        :institution
        :total-count="meetingsCount"
        :requires-action="activityStatus.requires_action"
        @view-all="$emit('navigate-tab', 'meetings')"
        @schedule-meeting="$emit('schedule-meeting')"
        @view-meeting="(meeting) => $emit('view-meeting', meeting)"
      />

      <!-- Empty meetings state -->
      <SectionCard v-else :title="$t('Paskutiniai susitikimai')" :icon="CalendarIcon" empty>
        <template #empty>
          <div class="py-8 text-center">
            <CalendarIcon class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
            <p class="mb-3 text-sm text-muted-foreground">
              {{ $t('Nėra susitikimų') }}
            </p>
            <Button size="sm" class="gap-2" @click="$emit('schedule-meeting')">
              <CalendarIcon class="h-4 w-4" />
              {{ $t('Suplanuoti susitikimą') }}
            </Button>
          </div>
        </template>
      </SectionCard>
    </div>

    <!-- Tasks + Discussion -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <InstitutionTasksPreview
        :tasks="institution.allTasks"
        @view-all="$emit('navigate-tab', 'tasks')"
      />
      <InstitutionDiscussionPreview
        :comments="recentComments"
        :comments-count="institution.comments_count"
        @view-all="$emit('navigate-tab', 'discussion')"
      />
    </div>

    <!-- Related institutions -->
    <InstitutionRelatedPreview
      v-if="relatedInstitutions.length > 0"
      :related="relatedInstitutions"
      @view-all="$emit('navigate-tab', 'related')"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Users,
  UserPlus,
  Calendar as CalendarIcon,
  Info,
  AlertTriangle,
  ChevronRight,
  CheckCircle2,
  CalendarClock,
  CalendarCheck,
  CalendarX,
  Clock,
} from 'lucide-vue-next';

import InstitutionMeetingsPreview from './InstitutionMeetingsPreview.vue';
import InstitutionTasksPreview from './InstitutionTasksPreview.vue';
import InstitutionDiscussionPreview from './InstitutionDiscussionPreview.vue';
import InstitutionRelatedPreview from './InstitutionRelatedPreview.vue';

import UserPopover from '@/Components/Avatars/UserPopover.vue';
import { SectionCard } from '@/Components/ui/section-card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { useInstitutionUrgency } from '@/Composables/useInstitutionUrgency';
import { interactiveCardClass } from '@/Utils/interactiveCard';
import { formatStaticTime } from '@/Utils/IntlTime';
import type { InstitutionPageData } from '@/Types/InstitutionPage';

const MEMBER_PREVIEW_LIMIT = 6;

const props = defineProps<{
  institution: InstitutionPageData;
  canEditMembers?: boolean;
}>();

defineEmits<{
  'navigate-tab': [tab: string];
  'schedule-meeting': [];
  'report-activity': [];
  'add-member': [];
  'view-profile': [member: App.Entities.User];
  'edit-member': [member: App.Entities.User];
  'view-meeting': [meeting: App.Entities.Meeting];
}>();

// Use urgency composable
const {
  lastMeeting,
  totalPositions,
  filledPositions,
} = useInstitutionUrgency(() => props.institution);

const activityStatus = computed(() => props.institution.activity_status);
const activityLabel = computed(() => $t(`visak.activity.activity_status.${activityStatus.value.status}`));
const activityIcon = computed(() => ({
  no_activity: CalendarX,
  healthy: CheckCircle2,
  approaching: Clock,
  overdue: AlertTriangle,
  covered_by_upcoming_meeting: CalendarClock,
  covered_by_check_in: CalendarCheck,
}[activityStatus.value.status]));
const activityPanelClass = computed(() => ({
  no_activity: 'border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300',
  healthy: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
  approaching: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300',
  overdue: 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-800 dark:bg-orange-950/30 dark:text-orange-300',
  covered_by_upcoming_meeting: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300',
  covered_by_check_in: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300',
}[activityStatus.value.status]));

// Description (localized string via toArray())
const description = computed(() => {
  const value = props.institution.description;
  return typeof value === 'string' && value.trim() !== '' ? value : null;
});

// Members
const members = computed<App.Entities.User[]>(() => props.institution.current_users ?? []);
const previewMembers = computed(() => members.value.slice(0, MEMBER_PREVIEW_LIMIT));

const showCapacityWarning = computed(() => {
  return totalPositions.value > 0 && members.value.length > totalPositions.value;
});

// Map each member to the name of a duty they currently hold (for a role badge).
const dutyNameByMemberId = computed(() => {
  const map = new Map<string, string>();
  (props.institution.duties ?? []).forEach((duty) => {
    (duty.current_users ?? []).forEach((user) => {
      if (!map.has(String(user.id))) {
        map.set(String(user.id), duty.name);
      }
    });
  });
  return map;
});

const roleForMember = (member: App.Entities.User): string | undefined => {
  return dutyNameByMemberId.value.get(String(member.id));
};

// Meetings
const meetingsCount = computed(() => props.institution.meetings?.length || 0);

const recentMeetings = computed(() => {
  const meetings = props.institution.meetings || [];
  return [...meetings]
    .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())
    .slice(0, 3);
});

// Discussion preview (provided by the controller)
const recentComments = computed(() => props.institution.recentComments ?? []);

// Related institutions (flat format from the controller)
const relatedInstitutions = computed(() => props.institution.relatedInstitutionsFlat ?? []);

const formatLastMeetingDate = (dateString: string) => {
  return formatStaticTime(new Date(dateString), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};
</script>
