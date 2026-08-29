<template>
  <section class="pb-16">
    <div class="max-w-3xl">
      <!-- Meeting header card with gradient styling -->
      <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100 p-6 sm:p-8 ring-1 ring-zinc-200/50 dark:from-zinc-900 dark:to-zinc-800 dark:ring-zinc-700/50">
        <!-- Decorative blur elements -->
        <div class="absolute -right-20 -top-20 size-64 rounded-full bg-vusa-red/5 blur-3xl" />
        <div class="absolute -bottom-10 -left-10 size-48 rounded-full bg-vusa-yellow/5 blur-3xl" />

        <div class="relative">
          <!-- Institution link - subtle top line -->
          <InertiaLink
            :href="route('contacts.institution', { institution: institution.id, lang: page.props.app.locale, subdomain: page.props.tenant?.subdomain || 'www' })"
            class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors mb-3"
          >
            <Building2 class="h-4 w-4" />
            {{ institution.name }}
          </InertiaLink>

          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
            <div class="flex-1">
              <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight">
                {{ formatMeetingDateTime(meeting) }}
              </h1>
              <p v-if="meeting.description" class="text-zinc-600 dark:text-zinc-400 mt-2 text-sm leading-relaxed">
                {{ meeting.description }}
              </p>
            </div>

            <!-- Info button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button v-if="requiresStudentPerspective" variant="ghost" size="icon" class="h-8 w-8 text-zinc-400 hover:text-zinc-600" @click="showInfoModal = true">
                    <InfoIcon class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Apie balsavimo skaidrumą') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          <!-- Meeting summary - cleaner layout -->
          <div class="flex flex-wrap items-center gap-3 mb-5">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
              {{ allAgendaItems.length }}
              {{ allAgendaItems.length === 1 ? $t('klausimas') : $t('klausimai') }}
            </span>
            <AgendaOutcomeIndicators v-if="requiresStudentPerspective" :agenda-items="itemsWithDecisions" />
          </div>

          <!-- Student Representatives Section - cleaner -->
          <div class="pt-5 border-t border-zinc-200/80 dark:border-zinc-700/80">
            <h3 class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-3">
              {{ $t('Studentų atstovai') }}
            </h3>
            <div v-if="representatives && representatives.length > 0" class="flex flex-wrap items-center gap-3">
              <div
                v-for="user in representatives"
                :key="user.id"
                class="flex items-center gap-2"
              >
                <UserAvatar :user :size="28" border />
                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ user.name }}</span>
              </div>
            </div>
            <p v-else class="text-sm text-zinc-400 dark:text-zinc-500 italic">
              {{ $t('Atstovai nežinomi') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Outcome Summary Card (when there are decisions) -->
      <div
        v-if="requiresStudentPerspective && outcomeSummary.total > 0"
        class="mb-8 overflow-hidden rounded-xl bg-white dark:bg-zinc-900 p-5 ring-1 ring-zinc-200 dark:ring-zinc-800 shadow-sm"
      >
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-50 mb-4">
          {{ $t('Sprendimų santrauka') }}
        </h3>
        <div class="grid grid-cols-3 gap-4">
          <!-- Positive decisions -->
          <div class="text-center p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ outcomeSummary.positive }}
            </div>
            <div class="text-xs text-emerald-700 dark:text-emerald-300 mt-1">
              {{ $t('Teigiami') }}
            </div>
          </div>
          <!-- Neutral decisions -->
          <div class="text-center p-3 rounded-lg bg-zinc-100 dark:bg-zinc-800">
            <div class="text-2xl font-bold text-zinc-600 dark:text-zinc-400">
              {{ outcomeSummary.neutral }}
            </div>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">
              {{ $t('Neutralūs') }}
            </div>
          </div>
          <!-- Negative decisions -->
          <div class="text-center p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">
              {{ outcomeSummary.negative }}
            </div>
            <div class="text-xs text-red-700 dark:text-red-300 mt-1">
              {{ $t('Neigiami') }}
            </div>
          </div>
        </div>
        <!-- Student alignment summary -->
        <div v-if="outcomeSummary.alignedCount > 0" class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
          <div class="flex items-center justify-between text-sm">
            <span class="text-zinc-600 dark:text-zinc-400">
              {{ $t('Studentų pozicijos atitiko sprendimą') }}:
            </span>
            <span
              :class="[
                'font-medium',
                outcomeSummary.alignmentRate >= 70
                  ? 'text-emerald-600 dark:text-emerald-400'
                  : outcomeSummary.alignmentRate >= 50
                    ? 'text-amber-600 dark:text-amber-400'
                    : 'text-red-600 dark:text-red-400'
              ]"
            >
              {{ outcomeSummary.alignedCount }} / {{ outcomeSummary.comparableCount }}
              ({{ outcomeSummary.alignmentRate }}%)
            </span>
          </div>
        </div>
      </div>

      <PublicAgendaList
        :items="allAgendaItems"
        :requires-student-perspective="requiresStudentPerspective"
        :is-upcoming="isUpcoming"
      />

      <!-- Documents produced by the meeting: nutarimai, protokolai. -->
      <section v-if="documents.length" class="mt-10">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50 mb-4">
          {{ $t('Dokumentai') }}
        </h2>
        <PublicMeetingDocuments :documents />
      </section>

      <!-- Previous/Next Meeting Navigation -->
      <nav v-if="previousMeeting || nextMeeting" class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800">
        <div class="flex flex-col sm:flex-row justify-between gap-4">
          <InertiaLink
            v-if="previousMeeting"
            :href="route('publicMeetings.show', { meeting: previousMeeting.id, lang: page.props.app.locale, subdomain: page.props.tenant?.subdomain || 'www' })"
            class="flex items-center gap-3 group"
          >
            <ChevronLeft class="h-5 w-5 text-zinc-300 group-hover:text-zinc-500 dark:text-zinc-600 dark:group-hover:text-zinc-400 transition-colors" />
            <div class="text-left">
              <span class="text-xs uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $t('Ankstesnis posėdis') }}</span>
              <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-zinc-200 transition-colors">
                {{ formatMeetingDateTime(previousMeeting) }}
              </p>
            </div>
          </InertiaLink>
          <div v-else />

          <InertiaLink
            v-if="nextMeeting"
            :href="route('publicMeetings.show', { meeting: nextMeeting.id, lang: page.props.app.locale, subdomain: page.props.tenant?.subdomain || 'www' })"
            class="flex items-center gap-3 group sm:ml-auto"
          >
            <div class="text-right">
              <span class="text-xs uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $t('Kitas posėdis') }}</span>
              <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-zinc-200 transition-colors">
                {{ formatMeetingDateTime(nextMeeting) }}
              </p>
            </div>
            <ChevronRight class="h-5 w-5 text-zinc-300 group-hover:text-zinc-500 dark:text-zinc-600 dark:group-hover:text-zinc-400 transition-colors" />
          </InertiaLink>
        </div>
      </nav>
    </div>

    <!-- Info modal -->
    <PublicVotingExplainerModal v-model:open="showInfoModal" />

    <FeedbackPopover />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage, Link as InertiaLink } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { InfoIcon, CheckCircleIcon, AlertCircleIcon, ChevronLeft, ChevronRight, Building2, Users as UsersIcon } from 'lucide-vue-next';

import { formatMeetingDateTime } from '@/Utils/MeetingDisplay';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { getMainVote, getMeetingStatusSummary, hasDecisionData, getVoteAlignmentLabel, canCompareVotes, isVoteAligned, getVoteComparisonText, getVoteComparisonColorClass, type AgendaItemStatus } from '@/Composables/useAgendaItemStyling';
import PublicVotingExplainerModal from '@/Components/Public/PublicVotingExplainerModal.vue';
import VoteStatusIndicator from '@/Components/Public/VoteStatusIndicator.vue';
import AgendaOutcomeIndicators from '@/Components/Public/AgendaOutcomeIndicators.vue';
import PublicAgendaList from '@/Components/Public/PublicAgendaList.vue';
import PublicMeetingDocuments, { type PublicMeetingDocument } from '@/Components/Public/PublicMeetingDocuments.vue';
import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import IFluentPeople24Regular from '~icons/fluent/people-24-regular';

const props = defineProps<{
  meeting: App.Entities.Meeting;
  institution: App.Entities.Institution;
  representatives: App.Entities.User[];
  previousMeeting?: { id: string; start_time: string; type?: string | null } | null;
  nextMeeting?: { id: string; start_time: string; type?: string | null } | null;
  documents?: PublicMeetingDocument[];
  /** False for VU SA's own bodies — see Meeting::requiresStudentPerspective(). */
  requiresStudentPerspective?: boolean;
  calendarEvent?: { id: number; title: string; date: string } | null;
}>();

const page = usePage();
const showInfoModal = ref(false);

const documents = computed(() => props.documents ?? []);
const requiresStudentPerspective = computed(() => props.requiresStudentPerspective ?? true);
const isUpcoming = computed(() => new Date(props.meeting.start_time) > new Date());

// Deferred rendering for long agendas
const deferredContentReady = ref(false);
onMounted(() => {
  requestAnimationFrame(() => {
    setTimeout(() => {
      deferredContentReady.value = true;
    }, 100);
  });
});

// All agenda items (show all, not filtered)
const allAgendaItems = computed(() => {
  return props.meeting.agenda_items || [];
});

// Outcome summary for the summary card - use composable's getMeetingStatusSummary
const outcomeSummary = computed(() => {
  const summary = getMeetingStatusSummary(allAgendaItems.value, requiresStudentPerspective.value);
  const items = allAgendaItems.value;

  // Count decision types for display
  let positive = 0;
  let negative = 0;
  let neutral = 0;

  for (const item of items) {
    const mainVote = getMainVote(item);
    if (!mainVote) continue;

    if (mainVote.decision === 'positive') positive++;
    else if (mainVote.decision === 'negative') negative++;
    else if (mainVote.decision === 'neutral') neutral++;
  }

  const total = positive + negative + neutral;

  return {
    total,
    positive,
    negative,
    neutral,
    alignedCount: summary.aligned,
    comparableCount: summary.aligned + summary.misaligned,
    alignmentRate: summary.alignmentRate,
  };
});

// Items with decision data (for the outcome indicators in header)
const itemsWithDecisions = computed(() => {
  return allAgendaItems.value.filter(hasDecisionData);
});

// Set breadcrumbs for meeting page
usePageBreadcrumbs(() => {
  const items = [];

  // Institution contacts link
  items.push(
    BreadcrumbHelpers.createRouteBreadcrumb(
      props.institution.name,
      'contacts.institution',
      {
        institution: props.institution.id,
        lang: page.props.app.locale,
        subdomain: page.props.tenant?.subdomain || 'www',
      },
      IFluentPeople24Regular,
    ),
  );

  // Current meeting
  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(
      formatMeetingDateTime(props.meeting),
      undefined,
    ),
  );

  return items;
});

// Use composable functions for vote outcome display
const getVoteOutcomeIcon = (item: App.Entities.AgendaItem) => {
  return isVoteAligned(item) ? CheckCircleIcon : AlertCircleIcon;
};
</script>
