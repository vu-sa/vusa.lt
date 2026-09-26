<template>
  <div class="divide-y divide-border border-y border-border" data-slot="institution-meetings-list">
    <button
      v-for="meeting in sortedMeetings"
      :key="meeting.id"
      type="button"
      :class="[
        'flex min-h-11 w-full items-center gap-4 px-2 py-3 text-left sm:px-3',
        'transition-colors hover:bg-accent',
        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
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
        <span v-if="meeting.has_protocol" class="inline-flex items-center gap-1 text-xs text-muted-foreground">
          <FileCheck class="size-3.5" aria-hidden="true" />
          {{ $t('Protokolas') }}
        </span>
        <span v-if="meeting.has_report" class="inline-flex items-center gap-1 text-xs text-muted-foreground">
          <ClipboardCheck class="size-3.5" aria-hidden="true" />
          {{ $t('Ataskaita') }}
        </span>
      </div>

      <div class="flex shrink-0 items-center gap-1">
        <Button
          v-if="canDelete"
          variant="ghost"
          size="sm"
          class="size-8 p-0 pointer-coarse:size-11"
          :aria-label="$t('Ištrinti')"
          @click.stop="$emit('delete', meeting)"
        >
          <Trash2 class="size-3.5 text-muted-foreground" />
        </Button>
        <ChevronRight class="h-4 w-4 text-muted-foreground" />
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, ClipboardCheck, FileCheck, Trash2 } from 'lucide-vue-next';

import MeetingOutcomeIndicators from '@/Components/Public/Search/MeetingOutcomeIndicators.vue';
import { Button } from '@/Components/ui/button';
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
