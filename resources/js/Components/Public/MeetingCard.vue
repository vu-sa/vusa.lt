<template>
  <SmartLink
    :href="route('publicMeetings.show', { meeting: meeting.id, subdomain: $page.props.tenant?.subdomain })"
    data-slot="meeting-card"
    class="group block border border-border bg-card text-foreground transition-colors hover:border-foreground/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
  >
    <div class="flex items-start justify-between gap-4 p-4">
      <div class="min-w-0 flex-1">
        <time class="block text-sm font-semibold text-foreground">
          {{ formatMeetingDateTime(meeting) }}
        </time>

        <p v-if="showInstitution && meeting.institutions?.[0]" class="mt-1 text-xs font-medium text-muted-foreground">
          {{ meeting.institutions[0].name }}
        </p>

        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5">
          <span class="text-xs text-muted-foreground">
            {{ allAgendaItems.length }}
            {{ allAgendaItems.length === 1 ? $t('klausimas') : $t('klausimai') }}
          </span>
          <AgendaOutcomeIndicators :agenda-items="itemsWithDecisions" :requires-student-perspective="meeting.requires_student_perspective ?? true" />
          <span
            v-if="alignment"
            :class="['inline-flex items-center gap-1 border px-1.5 py-0.5 text-xs font-medium', statusRoleClasses[alignment.role]]"
          >
            <component :is="alignment.icon" class="size-3.5" aria-hidden="true" />
            {{ alignment.label }}
          </span>
        </div>
      </div>

      <span class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground transition-colors group-hover:text-brand">
        {{ $t('Peržiūrėti') }}
        <IFluentArrowRight24Regular class="size-3.5 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
      </span>
    </div>

    <ol v-if="allAgendaItems.length > 0" class="divide-y divide-border border-t border-border px-4">
      <li v-for="item in allAgendaItems" :key="item.id" class="py-2.5 text-xs">
        <div class="flex items-start gap-2">
          <p class="min-w-0 flex-1 text-sm font-medium text-foreground">
            {{ item.order }}. {{ item.title }}
          </p>
          <span
            v-if="item.brought_by_students"
            class="inline-flex shrink-0 items-center gap-1 border border-border px-1.5 py-0.5 text-xs font-medium text-muted-foreground"
          >
            <IFluentPeople24Regular class="size-3.5" aria-hidden="true" />
            {{ $t('Įtraukta studentų') }}
          </span>
        </div>
        <div v-if="hasDecisionData(item)" class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-muted-foreground">
          <span class="flex items-center gap-1">
            {{ $t('Studentų balsas') }}:
            <VoteStatusIndicator :vote="getMainVote(item)?.student_vote" type="vote" compact />
          </span>
          <span class="flex items-center gap-1">
            {{ $t('Sprendimas') }}:
            <VoteStatusIndicator :vote="getMainVote(item)?.decision" type="vote" compact />
          </span>
          <span class="flex items-center gap-1">
            {{ $t('Nauda') }}:
            <VoteStatusIndicator :vote="getMainVote(item)?.student_benefit" type="benefit" compact />
          </span>
        </div>
      </li>
    </ol>
  </SmartLink>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import AgendaOutcomeIndicators from './AgendaOutcomeIndicators.vue';
import VoteStatusIndicator from './VoteStatusIndicator.vue';
import SmartLink from './SmartLink.vue';

import { statusRoleClasses, type StatusRole } from '@/Constants/statuses';
import { formatMeetingDateTime } from '@/Utils/MeetingDisplay';
import IFluentArrowRight24Regular from '~icons/fluent/arrow-right-24-regular';
import IFluentCheckmark24Regular from '~icons/fluent/checkmark-24-regular';
import IFluentDismiss24Regular from '~icons/fluent/dismiss-24-regular';
import IFluentPeople24Regular from '~icons/fluent/people-24-regular';
import IFluentSubtract24Regular from '~icons/fluent/subtract-24-regular';
import IFluentWarning24Regular from '~icons/fluent/warning-24-regular';
import { getMainVote, getMeetingStatusSummary, hasDecisionData } from '@/Composables/useAgendaItemStyling';

const $page = usePage();

const props = withDefaults(defineProps<{
  meeting: App.Entities.Meeting;
  showInstitution?: boolean; // Show institution name (for search results)
}>(), {
  showInstitution: false,
});

// All agenda items count
const allAgendaItems = computed(() => {
  return props.meeting.agenda_items || [];
});

// Meeting summary for alignment status
const meetingSummary = computed(() => {
  return getMeetingStatusSummary(allAgendaItems.value, props.meeting.requires_student_perspective ?? true);
});

const ALIGNMENTS = {
  all_match: { label: 'Pozicija priimta', role: 'success', icon: IFluentCheckmark24Regular },
  mixed: { label: 'Mišrus rezultatas', role: 'attention', icon: IFluentWarning24Regular },
  all_mismatch: { label: 'Pozicija nepriimta', role: 'danger', icon: IFluentDismiss24Regular },
  neutral: { label: 'Neutralu', role: 'neutral', icon: IFluentSubtract24Regular },
} as const satisfies Record<string, { label: string; role: StatusRole; icon: unknown }>;

const alignment = computed(() => {
  const status = meetingSummary.value.voteAlignmentStatus;
  if (status === 'unknown' || meetingSummary.value.totalItems === 0) {
    return null;
  }

  const { label, role, icon } = ALIGNMENTS[status];
  return { label: $t(label), role, icon };
});

// Items with at least one decision field filled (for outcome indicators)
const itemsWithDecisions = computed(() => {
  return allAgendaItems.value.filter(hasDecisionData);
});

</script>
