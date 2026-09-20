<template>
  <div class="space-y-8" data-slot="institution-overview">
    <section :aria-label="$t('Veikla')" class="space-y-3" data-testid="institution-activity">
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <!-- The normal, healthy state paints no badge (rules/visual.md → Picking the role). -->
        <StatusBadge v-if="activityStatus.status !== 'healthy'" :status="activityPresentation" />
        <span v-else class="text-sm font-medium text-foreground">{{ $t('Veikla atnaujinta') }}</span>
        <span v-if="activityDetail" class="text-sm text-muted-foreground tabular-nums">{{ activityDetail }}</span>
      </div>

      <div v-if="activityStatus.requires_action" class="flex flex-wrap gap-2">
        <Button variant="brand" size="sm" class="pointer-coarse:h-11" @click="$emit('schedule-meeting')">
          <CalendarIcon class="size-4" aria-hidden="true" />
          {{ $t('tasks.periodicity_gap.schedule_meeting') }}
        </Button>
        <Button variant="outline" size="sm" class="pointer-coarse:h-11" @click="$emit('report-activity')">
          <Clock class="size-4" aria-hidden="true" />
          {{ $t('tasks.periodicity_gap.report_no_meeting') }}
        </Button>
      </div>

      <p v-if="lastMeetingAt" class="text-sm text-muted-foreground">
        {{ $t('Paskutinis susitikimas') }}: <span class="font-medium text-foreground">{{ formatLongDate(lastMeetingAt) }}</span>
      </p>
    </section>

    <section v-if="description" class="space-y-2">
      <h3 class="border-b border-border pb-2 text-base font-semibold text-foreground">
        {{ $t('Apie') }}
      </h3>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="prose prose-sm dark:prose-invert max-w-none" v-html="description" />
    </section>

    <section class="space-y-2">
      <div class="flex items-center justify-between border-b border-border pb-2">
        <h3 class="text-base font-semibold text-foreground">
          {{ $t('Paskutiniai susitikimai') }}
        </h3>
        <button
          v-if="overview.recentMeetings.length > 0"
          type="button"
          class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground pointer-coarse:min-h-11"
          @click="$emit('navigate-tab', 'meetings')"
        >
          {{ $t('Visi susitikimai') }}
          <ChevronRight class="size-4" aria-hidden="true" />
        </button>
      </div>

      <InstitutionMeetingsList
        v-if="overview.recentMeetings.length > 0"
        :meetings="recentMeetings"
        :institution-name="institution.name"
        @select="(meeting) => $emit('view-meeting', meeting)"
      />
      <EmptyState
        v-else
        :title="$t('Nėra susitikimų')"
        :description="$t('Šiai institucijai dar nėra suplanuota susitikimų.')"
        :icon="CalendarIcon"
        :action-label="$t('Suplanuoti susitikimą')"
        @action="$emit('schedule-meeting')"
      />
    </section>

    <section v-if="institution.managers?.length || secretaries.length" class="grid gap-6 sm:grid-cols-2">
      <div v-if="institution.managers?.length" class="space-y-2">
        <h3 class="border-b border-border pb-2 text-base font-semibold text-foreground">
          {{ $t('Koordinatoriai') }}
        </h3>
        <UsersAvatarGroup :users="institution.managers" :max="5" :size="32" />
      </div>
      <!-- Nominated for the current term (O22). Distinct from the body's members: a secretary
           need not hold a duty here at all. -->
      <div v-if="secretaries.length" class="space-y-2">
        <h3 class="border-b border-border pb-2 text-base font-semibold text-foreground">
          {{ $t('secretaries.label') }}
        </h3>
        <UsersAvatarGroup :users="(secretaries as unknown as App.Entities.User[])" :max="5" :size="32" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar as CalendarIcon, ChevronRight, Clock } from 'lucide-vue-next';

import InstitutionMeetingsList from './InstitutionMeetingsList.vue';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { institutionActivityStatuses } from '@/Constants/statuses';
import type { InstitutionActivityStatus as ActivityStatusEnum } from '@/Types/enums';
import type { InstitutionOverviewData, InstitutionPageData, InstitutionPageMeeting } from '@/Types/InstitutionPage';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  institution: InstitutionPageData;
  overview: InstitutionOverviewData;
}>();

defineEmits<{
  'navigate-tab': [tab: string];
  'schedule-meeting': [];
  'report-activity': [];
  'view-meeting': [meeting: InstitutionPageMeeting];
}>();

const activityStatus = computed(() => props.overview.activity_status);
const activityPresentation = computed(() => institutionActivityStatuses[activityStatus.value.status as ActivityStatusEnum]);

const formatLongDate = (value: string) => formatDate(value, { format: 'full' });

const activityDetail = computed(() => {
  const status = activityStatus.value;

  if (status.effective_days_since_activity !== null) {
    return `${status.effective_days_since_activity} ${$t('d.')} / ${status.periodicity_days} ${$t('d.')}`;
  }

  if (status.next_meeting_at) {
    return formatLongDate(status.next_meeting_at);
  }

  if (status.active_check_in_until) {
    return `${$t('iki')} ${formatLongDate(status.active_check_in_until)}`;
  }

  return null;
});

const lastMeetingAt = computed(() => activityStatus.value.last_meeting_at ?? props.overview.recentMeetings[0]?.start_time ?? null);

// Description (localized string via toArray())
const description = computed(() => {
  const value = props.institution.description;

  return typeof value === 'string' && value.trim() !== '' ? value : null;
});

const secretaries = computed(() => props.institution.secretaries ?? []);

const recentMeetings = computed(() => [...props.overview.recentMeetings]
  .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())
  .slice(0, 3));
</script>
