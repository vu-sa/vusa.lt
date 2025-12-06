<template>
  <div class="relative pl-8">
    <!-- Timeline dot with status color -->
    <div
      class="absolute left-0 top-3 h-3 w-3 rounded-full border-2 border-background"
      :class="getStatusDotColor()"
    />

    <!-- Timeline line (connects dots) -->
    <div
      v-if="!isLast"
      class="absolute left-[5px] top-6 w-0.5 h-[calc(100%+0.75rem)] bg-border"
    />

    <!-- Meeting card -->
    <div
      class="group border rounded-lg hover:shadow-md transition-all"
      :class="[
        getStatusBorderColor(),
        'bg-card'
      ]"
    >
      <!-- Compact header -->
      <div class="p-4 pb-3">
        <div class="flex items-start justify-between gap-4 mb-2">
          <!-- Date & Time -->
          <div class="flex-1 min-w-0">
            <time class="text-sm font-semibold text-foreground block">
              {{ formatMeetingDate(meeting.start_time) }}
            </time>

            <!-- Institution name (when shown in search context) -->
            <div v-if="showInstitution && meeting.institutions?.[0]" class="mt-1">
              <span class="text-xs font-medium text-muted-foreground">
                {{ meeting.institutions[0].name }}
              </span>
            </div>

            <!-- Outcome indicators (replacing progress bar) -->
            <div class="mt-1.5 flex items-center gap-2">
              <span class="text-xs text-muted-foreground">
                {{ allAgendaItems.length }}
                {{ allAgendaItems.length === 1 ? $t('klausimas') : $t('klausimai') }}
              </span>
              <AgendaOutcomeIndicators :agenda-items="itemsWithDecisions" />
            </div>
          </div>

          <!-- Completion status -->
          <Badge
            :variant="getCompletionVariant(meeting.completion_status)"
            size="sm"
            class="shrink-0"
          >
            {{ getCompletionLabel(meeting.completion_status) }}
          </Badge>
        </div>

        <!-- Compact "View full" action -->
        <div class="flex justify-end">
          <Button
            variant="ghost"
            size="sm"
            class="h-6 px-2 text-xs"
            as-child
          >
            <Link :href="route('publicMeetings.show', { meeting: meeting.id, subdomain: $page.props.tenant?.subdomain })">
              {{ $t('Peržiūrėti') }}
              <ArrowRightIcon class="h-3 w-3 ml-1" />
            </Link>
          </Button>
        </div>
      </div>

      <!-- Agenda items (showing all items, vote details only when available) -->
      <div
        v-if="allAgendaItems.length > 0"
        class="border-t bg-muted/30 px-4 py-3"
      >
        <div class="space-y-2">
          <div
            v-for="item in allAgendaItems"
            :key="item.id"
            class="text-xs"
          >
            <p class="font-medium text-foreground mb-1">
              {{ item.order }}. {{ item.title }}
            </p>
            <!-- Vote details only when at least one value exists -->
            <div v-if="hasDecisionData(item)" class="flex gap-4 text-muted-foreground">
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
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ArrowRightIcon } from 'lucide-vue-next';
import AgendaOutcomeIndicators from './AgendaOutcomeIndicators.vue';
import VoteIndicator from './VoteIndicator.vue';
import { formatStaticTime } from '@/Utils/IntlTime';

const props = withDefaults(defineProps<{
  meeting: App.Entities.Meeting;
  isLast?: boolean;  // Don't draw timeline line for last item
  showInstitution?: boolean;  // Show institution name (for search results)
}>(), {
  isLast: false,
  showInstitution: false
});

const hasAgendaItems = computed(() => {
  return props.meeting.agenda_items && props.meeting.agenda_items.length > 0;
});

// All agenda items count
const allAgendaItems = computed(() => {
  return props.meeting.agenda_items || [];
});

// Items with at least one decision field filled (for outcome indicators)
const itemsWithDecisions = computed(() => {
  return allAgendaItems.value.filter(item =>
    item.student_vote !== null || item.decision !== null || item.student_benefit !== null
  );
});

// Check if an agenda item has any decision data to show
const hasDecisionData = (item: App.Entities.AgendaItem) => {
  return item.student_vote !== null || item.decision !== null || item.student_benefit !== null;
};

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

const getStatusDotColor = () => {
  const status = props.meeting.completion_status;
  return {
    'complete': 'bg-green-500 dark:bg-green-400',
    'incomplete': 'bg-amber-500 dark:bg-amber-400',
    'no_items': 'bg-zinc-400 dark:bg-zinc-500',
  }[status] || 'bg-zinc-400';
};

const getStatusBorderColor = () => {
  const status = props.meeting.completion_status;
  return {
    'complete': 'border-green-200 dark:border-green-800/30',
    'incomplete': 'border-amber-200 dark:border-amber-800/30',
    'no_items': 'border-border',
  }[status] || 'border-border';
};
</script>
