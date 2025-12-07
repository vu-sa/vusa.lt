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
          <inertia-link
            :href="route('contacts.institution', { institution: institution.id, lang: page.props.app.locale, subdomain: page.props.tenant?.subdomain || 'www' })"
            class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors mb-3"
          >
            <Building2 class="h-4 w-4" />
            {{ institution.name }}
          </inertia-link>

          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
            <div class="flex-1">
              <h1 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-zinc-50 tracking-tight">
                {{ formatMeetingDate(meeting.start_time) }}
              </h1>
              <p v-if="meeting.description" class="text-zinc-600 dark:text-zinc-400 mt-2 text-sm leading-relaxed">
                {{ meeting.description }}
              </p>
            </div>

            <!-- Info button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button variant="ghost" size="icon" class="h-8 w-8 text-zinc-400 hover:text-zinc-600" @click="showInfoModal = true">
                    <InfoIcon class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Apie balsavimo skaidrumą') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          <!-- Meeting summary - cleaner layout -->
          <div class="flex flex-wrap items-center gap-3 mb-5">
            <Badge :variant="getCompletionVariant(meeting.completion_status)">
              {{ getCompletionLabel(meeting.completion_status) }}
            </Badge>
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
              {{ allAgendaItems.length }}
              {{ allAgendaItems.length === 1 ? $t('klausimas') : $t('klausimai') }}
            </span>
            <AgendaOutcomeIndicators :agenda-items="itemsWithDecisions" />
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

      <!-- Agenda items section -->
      <div class="space-y-4">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50 mb-4">
          {{ $t('Darbotvarkė') }}
        </h2>

        <div
          v-for="item in allAgendaItems"
          :key="item.id"
          class="overflow-hidden rounded-xl bg-white dark:bg-zinc-900 p-5 ring-1 ring-zinc-200 dark:ring-zinc-800 shadow-sm"
        >
          <!-- Item title -->
          <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-50">
            <span class="text-zinc-400 dark:text-zinc-500 font-normal">{{ item.order }}.</span> {{ item.title }}
          </h3>

          <!-- Item description (ShowMeeting-specific) -->
          <p v-if="item.description" class="text-zinc-600 dark:text-zinc-400 mt-2 text-sm leading-relaxed">
            {{ item.description }}
          </p>

          <!-- Inline voting indicators (only show if at least one value exists) -->
          <div v-if="hasDecisionData(item)" class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-sm">
            <span class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
              {{ $t('Studentų balsas') }}:
              <VoteIndicator :vote="item.student_vote" type="vote" compact />
            </span>
            <span class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
              {{ $t('Sprendimas') }}:
              <VoteIndicator :vote="item.decision" type="vote" compact />
            </span>
            <span class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
              {{ $t('Nauda') }}:
              <VoteIndicator :vote="item.student_benefit" type="benefit" compact />
            </span>
            <!-- Vote comparison inline indicator -->
            <span 
              v-if="showVoteComparison(item)" 
              class="flex items-center gap-1.5 text-xs"
              :class="item.student_vote === item.decision 
                ? 'text-green-600 dark:text-green-500' 
                : 'text-amber-600 dark:text-amber-500'"
            >
              <component :is="getVoteOutcomeIcon(item)" class="h-3.5 w-3.5" />
              {{ getVoteOutcomeText(item) }}
            </span>
          </div>

          <!-- No decision data message -->
          <p v-else class="text-xs text-zinc-400 dark:text-zinc-500 italic mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
            {{ $t('Balsavimo duomenys dar neįvesti') }}
          </p>
        </div>

        <!-- No agenda items message -->
        <p v-if="allAgendaItems.length === 0" class="text-zinc-500 dark:text-zinc-400 italic text-center py-8">
          {{ $t('Darbotvarkė dar neįvesta') }}
        </p>
      </div>

      <!-- Previous/Next Meeting Navigation -->
      <nav v-if="previousMeeting || nextMeeting" class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800">
        <div class="flex flex-col sm:flex-row justify-between gap-4">
          <inertia-link
            v-if="previousMeeting"
            :href="route('publicMeetings.show', { meeting: previousMeeting.id, lang: page.props.app.locale, subdomain: page.props.tenant?.subdomain || 'www' })"
            class="flex items-center gap-3 group"
          >
            <ChevronLeft class="h-5 w-5 text-zinc-300 group-hover:text-zinc-500 dark:text-zinc-600 dark:group-hover:text-zinc-400 transition-colors" />
            <div class="text-left">
              <span class="text-xs uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $t('Ankstesnis posėdis') }}</span>
              <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-zinc-200 transition-colors">{{ formatMeetingDate(previousMeeting.start_time) }}</p>
            </div>
          </inertia-link>
          <div v-else />

          <inertia-link
            v-if="nextMeeting"
            :href="route('publicMeetings.show', { meeting: nextMeeting.id, lang: page.props.app.locale, subdomain: page.props.tenant?.subdomain || 'www' })"
            class="flex items-center gap-3 group sm:ml-auto"
          >
            <div class="text-right">
              <span class="text-xs uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $t('Kitas posėdis') }}</span>
              <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-zinc-200 transition-colors">{{ formatMeetingDate(nextMeeting.start_time) }}</p>
            </div>
            <ChevronRight class="h-5 w-5 text-zinc-300 group-hover:text-zinc-500 dark:text-zinc-600 dark:group-hover:text-zinc-400 transition-colors" />
          </inertia-link>
        </div>
      </nav>
    </div>

    <!-- Info modal -->
    <MeetingInfoModal v-model:open="showInfoModal" />

    <FeedbackPopover />
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage, Link as InertiaLink } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { InfoIcon, CheckCircleIcon, AlertCircleIcon, ChevronLeft, ChevronRight, Building2 } from 'lucide-vue-next';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { useMeetingStatus } from '@/Composables/useMeetingStatus';
import MeetingInfoModal from '@/Components/Public/MeetingInfoModal.vue';
import VoteIndicator from '@/Components/Public/VoteIndicator.vue';
import AgendaOutcomeIndicators from '@/Components/Public/AgendaOutcomeIndicators.vue';
import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { formatStaticTime } from '@/Utils/IntlTime';
import IFluentPeople24Regular from "~icons/fluent/people-24-regular";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  institution: App.Entities.Institution;
  representatives: App.Entities.User[];
  previousMeeting?: { id: string; start_time: string } | null;
  nextMeeting?: { id: string; start_time: string } | null;
}>();

const page = usePage();
const showInfoModal = ref(false);

// All agenda items (show all, not filtered)
const allAgendaItems = computed(() => {
  return props.meeting.agenda_items || [];
});

// Check if an agenda item has any decision data to show
const hasDecisionData = (item: App.Entities.AgendaItem) => {
  return item.student_vote !== null || item.decision !== null || item.student_benefit !== null;
};

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
        subdomain: page.props.tenant?.subdomain || 'www'
      },
      IFluentPeople24Regular
    )
  );

  // Current meeting
  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(
      formatMeetingDate(props.meeting.start_time),
      undefined
    )
  );

  return items;
});

const formatMeetingDate = (date: string) => {
  return formatStaticTime(new Date(date), {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Use shared meeting status utilities (with verbose labels for detail page)
const { getCompletionVariant, getCompletionLabel: getCompletionLabelBase } = useMeetingStatus();
const getCompletionLabel = (status: string) => getCompletionLabelBase(status, true);

const showVoteComparison = (item: App.Entities.AgendaItem) => {
  return item.student_vote && item.decision;
};

const getVoteOutcomeVariant = (item: App.Entities.AgendaItem) => {
  return item.student_vote === item.decision ? 'success' : 'warning';
};

const getVoteOutcomeIcon = (item: App.Entities.AgendaItem) => {
  return item.student_vote === item.decision ? CheckCircleIcon : AlertCircleIcon;
};

const getVoteOutcomeText = (item: App.Entities.AgendaItem) => {
  if (item.student_vote === item.decision) {
    return $t('Studentų pozicija priimta');
  }
  return $t('Studentų pozicija nesutampa su sprendimu');
};
</script>
