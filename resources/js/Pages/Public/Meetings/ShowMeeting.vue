<template>
  <section class="pt-8 pb-16">
    <div class="container mx-auto px-4">
      <!-- Meeting header card -->
      <Card class="mb-6">
        <CardContent class="p-6">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
            <div class="flex-1">
              <h1 class="text-3xl font-bold mb-2">
                {{ formatMeetingDate(meeting.start_time) }}
              </h1>
              <p v-if="meeting.description" class="text-muted-foreground">
                {{ meeting.description }}
              </p>
            </div>

            <!-- Info button -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button variant="outline" size="sm" @click="showInfoModal = true">
                    <InfoIcon class="h-4 w-4" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Apie balsavimo skaidrumą') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
          </div>

          <!-- Meeting summary (matching MeetingCard pattern) -->
          <div class="flex items-center gap-4">
            <Badge :variant="getCompletionVariant(meeting.completion_status)">
              {{ getCompletionLabel(meeting.completion_status) }}
            </Badge>
            <div class="flex items-center gap-2">
              <span class="text-sm text-muted-foreground">
                {{ allAgendaItems.length }}
                {{ allAgendaItems.length === 1 ? $t('klausimas') : $t('klausimai') }}
              </span>
              <AgendaOutcomeIndicators :agenda-items="itemsWithDecisions" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Agenda items (matching MeetingCard style) -->
      <div class="space-y-4">
        <h2 class="text-2xl font-bold mb-4">
          {{ $t('Darbotvarkė') }}
        </h2>

        <div
          v-for="item in allAgendaItems"
          :key="item.id"
          class="border-l-4 pl-6 py-4 rounded bg-card border"
          :class="getAgendaItemBorderClass(item)"
        >
          <!-- Item title -->
          <h3 class="text-lg font-semibold mb-2">
            {{ item.order }}. {{ item.title }}
          </h3>

          <!-- Item description (ShowMeeting-specific) -->
          <p v-if="item.description" class="text-muted-foreground mb-3 text-sm">
            {{ item.description }}
          </p>

          <!-- Inline voting indicators (only show if at least one value exists) -->
          <div v-if="hasDecisionData(item)" class="flex gap-4 text-sm flex-wrap">
            <span class="flex items-center gap-1">
              {{ $t('Studentų balsas') }}:
              <VoteIndicator :vote="item.student_vote" type="vote" compact />
            </span>
            <span class="flex items-center gap-1">
              {{ $t('Sprendimas') }}:
              <VoteIndicator :vote="item.decision" type="vote" compact />
            </span>
            <span class="flex items-center gap-1">
              {{ $t('Nauda') }}:
              <VoteIndicator :vote="item.student_benefit" type="benefit" compact />
            </span>
          </div>

          <!-- No decision data message -->
          <p v-else class="text-xs text-muted-foreground italic">
            {{ $t('Balsavimo duomenys dar neįvesti') }}
          </p>

          <!-- Vote comparison alert (ShowMeeting-specific feature) -->
          <Alert v-if="showVoteComparison(item)" :variant="getVoteOutcomeVariant(item)" class="mt-3">
            <AlertDescription class="flex items-center gap-2">
              <component :is="getVoteOutcomeIcon(item)" class="h-4 w-4" />
              {{ getVoteOutcomeText(item) }}
            </AlertDescription>
          </Alert>
        </div>

        <!-- No agenda items message -->
        <p v-if="allAgendaItems.length === 0" class="text-muted-foreground italic text-center py-8">
          {{ $t('Darbotvarkė dar neįvesta') }}
        </p>
      </div>
    </div>

    <!-- Info modal -->
    <MeetingInfoModal v-model:open="showInfoModal" />

    <FeedbackPopover />
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { InfoIcon, CheckCircleIcon, AlertCircleIcon } from 'lucide-vue-next';

import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import MeetingInfoModal from '@/Components/Public/MeetingInfoModal.vue';
import VoteIndicator from '@/Components/Public/VoteIndicator.vue';
import AgendaOutcomeIndicators from '@/Components/Public/AgendaOutcomeIndicators.vue';
import FeedbackPopover from '@/Components/Public/FeedbackPopover.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { formatStaticTime } from '@/Utils/IntlTime';
import IFluentPeople24Regular from "~icons/fluent/people-24-regular";

const props = defineProps<{
  meeting: App.Entities.Meeting;
  institution: App.Entities.Institution;
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

const getCompletionVariant = (status: string) => {
  return {
    'complete': 'success',
    'incomplete': 'warning',
    'no_items': 'secondary',
  }[status] || 'secondary';
};

const getCompletionLabel = (status: string) => {
  return {
    'complete': $t('Užpildyta'),
    'incomplete': $t('Neužpildyta'),
    'no_items': $t('Nėra darbotvarkės'),
  }[status] || status;
};

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

const getAgendaItemBorderClass = (item: App.Entities.AgendaItem) => {
  if (!item.student_vote || !item.decision) return 'border-zinc-300 dark:border-zinc-600';
  return item.student_vote === item.decision
    ? 'border-green-500 dark:border-green-400'
    : 'border-amber-500 dark:border-amber-400';
};
</script>
