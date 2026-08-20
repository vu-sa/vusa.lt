<template>
  <Card>
    <CardContent class="p-0">
      <button
        v-for="meeting in sortedMeetings"
        :key="meeting.id"
        type="button"
        :class="[
          'flex w-full items-center gap-4 px-4 py-3 text-left',
          'border-b border-border last:border-b-0',
          'transition-colors hover:bg-accent/50',
          'focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary/50',
        ]"
        @click="$emit('select', meeting)"
      >
        <span class="w-24 shrink-0 text-sm text-muted-foreground">
          {{ formatMeetingDate(meeting.start_time) }}
        </span>

        <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">
          {{ meetingTitle(meeting) }}
        </span>

        <div class="flex shrink-0 items-center gap-3 text-xs text-muted-foreground">
          <span v-if="meeting.agenda_items_count" class="whitespace-nowrap">
            {{ meeting.agenda_items_count }}
            {{ meeting.agenda_items_count === 1 ? $t('klausimas') : $t('klausimai') }}
          </span>
          <MeetingOutcomeIndicators
            v-if="hasVoteData(meeting)"
            :matches="meeting.vote_matches || 0"
            :mismatches="meeting.vote_mismatches || 0"
            :incomplete="meeting.incomplete_vote_data || 0"
          />
        </div>

        <div class="flex shrink-0 items-center gap-1.5">
          <Badge
            v-if="meeting.has_protocol"
            variant="outline"
            class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700"
          >
            <FileCheck class="h-3 w-3" />
            {{ $t('Protokolas') }}
          </Badge>
          <Badge
            v-if="meeting.has_report"
            variant="outline"
            class="text-xs gap-1 text-blue-600 border-blue-300 dark:text-blue-400 dark:border-blue-700"
          >
            <ClipboardCheck class="h-3 w-3" />
            {{ $t('Ataskaita') }}
          </Badge>
        </div>

        <div class="flex shrink-0 items-center gap-1">
          <Button
            v-if="canDelete"
            variant="ghost"
            size="sm"
            class="h-7 w-7 p-0"
            @click.stop="$emit('delete', meeting)"
          >
            <Trash2 class="h-3.5 w-3.5 text-muted-foreground" />
          </Button>
          <ChevronRight class="h-4 w-4 text-muted-foreground" />
        </div>
      </button>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, ClipboardCheck, FileCheck, Trash2 } from 'lucide-vue-next';

import MeetingOutcomeIndicators from '@/Components/Public/Search/MeetingOutcomeIndicators.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import type { InstitutionPageMeeting } from '@/Types/InstitutionPage';

const props = defineProps<{
  meetings: InstitutionPageMeeting[];
  /** Falls back into the title when a meeting has none of its own. */
  institutionName?: string;
  canDelete?: boolean;
}>();

defineEmits<{
  select: [meeting: InstitutionPageMeeting];
  delete: [meeting: InstitutionPageMeeting];
}>();

/** Newest first — the most recent meeting is nearly always the one being looked for. */
const sortedMeetings = computed(() =>
  [...props.meetings].sort(
    (a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime(),
  ),
);

const formatMeetingDate = (dateString: string) =>
  new Date(dateString).toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });

const meetingTitle = (meeting: InstitutionPageMeeting) => {
  if (meeting.title && meeting.title.trim() !== '') {
    return meeting.title;
  }
  return `${props.institutionName || 'Institucijos'} ${$t('posėdis')}`;
};

const hasVoteData = (meeting: InstitutionPageMeeting) =>
  (meeting.vote_matches || 0) + (meeting.vote_mismatches || 0) + (meeting.incomplete_vote_data || 0) > 0;
</script>
