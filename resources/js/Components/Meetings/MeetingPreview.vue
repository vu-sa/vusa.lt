<template>
  <PreviewPane
    entity-type="meeting"
    :eyebrow="$t('Posėdis')"
    :title="meeting.title || $t('Be pavadinimo')"
    :href="showHref"
  >
    <template v-if="completionStatus" #mark>
      <StatusMark :icon="MeetingIcon" :status="completionStatus" />
    </template>

    <template #subtitle>
      <Link v-if="meeting.institution_ids?.length" :href="route('institutions.show', meeting.institution_ids[0])" class="underline-offset-4 hover:text-brand hover:underline">
        {{ institutionName }}
      </Link>
      <template v-else>
        {{ institutionName }}
      </template>
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti posėdį') }}
          </Link>
        </Button>
      </slot>
    </template>

    <ContextFacts class="gap-x-6 gap-y-2 border-t-0 pt-0 text-sm">
      <ContextFact v-if="outcome.completion_status" :label="$t('Būsena')">
        <MeetingOutcome :outcome show="completion" show-complete />
      </ContextFact>
      <ContextFact v-if="hasVoteComparison" :label="$t('Balsavimas')">
        <MeetingOutcome :outcome show="votes" />
      </ContextFact>
      <ContextFact :label="titleOmitsDate(meeting.title) ? $t('Data') : $t('Laikas')" :value="titleOmitsDate(meeting.title) ? startsAt : startsAtTime" />
      <ContextFact v-if="meeting.tenant_shortname" :label="$t('Padalinys')" :value="meeting.tenant_shortname" />
      <ContextFact v-if="meeting.inactive_institution_ids?.length" :label="$t('Institucija')" :value="$t(inactiveInstitutionStatus.label)" />
    </ContextFacts>

    <PreviewSection
      v-if="isLoading || data?.representatives?.length"
      :title="$t('Atstovai')"
      :loading="isLoading"
    >
      <RepresentativesList :users="data?.representatives ?? []" />
    </PreviewSection>

    <PreviewSection
      :title="$t('Darbotvarkė')"
      :loading="isLoading"
      :empty="!data?.agenda_items?.length"
      :empty-message="$t('Nėra darbotvarkės klausimų')"
    >
      <ol class="divide-y divide-border border-y border-border">
        <li v-for="(item, index) in data?.agenda_items" :key="item.id">
          <Link
            :href="route('agendaItems.show', item.id)"
            class="flex items-start gap-3 py-2 transition-colors hover:text-brand pointer-coarse:min-h-11"
          >
            <span class="mt-0.5 text-xs tabular-nums text-muted-foreground">{{ index + 1 }}</span>
            <span class="min-w-0 flex-1 break-words text-sm">{{ item.title }}</span>
            <span
              v-if="agendaResult(item)"
              :class="['mt-0.5 shrink-0 text-xs font-medium', agendaResult(item)!.tone]"
              data-slot="agenda-decision"
            >
              {{ agendaResult(item)!.label }}
            </span>
          </Link>
        </li>
      </ol>
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { titleOmitsDate } from './meetingTitle';
import MeetingOutcome from './MeetingOutcome.vue';

import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import RepresentativesList from '@/Components/Avatars/RepresentativesList.vue';
import { MeetingIcon } from '@/Components/icons';
import { ContextFact, ContextFacts, PreviewPane, PreviewSection, StatusMark } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { usePreviewApi } from '@/Composables/usePreviewApi';
import { inactiveInstitutionStatus, meetingCompletionStatuses, statusFor, statusRoleParts, voteStatuses } from '@/Constants/statuses';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { formatDateTime, formatTime } from '@/Utils/dateTime';

interface MeetingPreviewData {
  outcome?: Partial<MeetingSearchResult>;
  agenda_items: Array<{ id: string; title: string; decision: string | null; type?: string | null }>;
  representatives: Array<{ id: string; name: string; profile_photo_path: string | null }>;
}

const props = defineProps<{
  meeting: MeetingSearchResult;
}>();

const { data, isLoading } = usePreviewApi<MeetingPreviewData>(route('api.v1.admin.meetings.preview', props.meeting.id));

const showHref = computed(() => route('meetings.show', props.meeting.id));
const institutionName = computed(() => getSearchTranslation(props.meeting, 'institution_name'));
// The API answers for this viewer, private items included; the search document never has them.
const outcome = computed<Partial<MeetingSearchResult>>(() => data.value?.outcome ?? props.meeting);
const hasVoteComparison = computed(() => (outcome.value.outcome_aligned ?? 0) + (outcome.value.outcome_misaligned ?? 0) > 0);
const completionStatus = computed(() => statusFor(meetingCompletionStatuses, outcome.value.completion_status));

const NON_VOTING_LABELS: Record<string, string> = { informational: 'Aptarimas', deferred: 'Atidėtas', break: 'Pertrauka', withdrawn: 'Išbrauktas' };

/** What became of an item: its vote, or the kind of item it was when there was none. */
function agendaResult(item: MeetingPreviewData['agenda_items'][number]): { label: string; tone: string } | null {
  const vote = statusFor(voteStatuses, item.decision);

  if (vote) {
    return { label: $t(vote.label), tone: statusRoleParts[vote.role].text };
  }

  return item.type && NON_VOTING_LABELS[item.type] ? { label: $t(NON_VOTING_LABELS[item.type]!), tone: 'text-muted-foreground' } : null;
}

// Search documents store Unix seconds.
const startsAtTime = computed(() => (props.meeting.start_time ? formatTime(new Date(props.meeting.start_time * 1000)) : null));
const startsAt = computed(() => (props.meeting.start_time ? formatDateTime(props.meeting.start_time * 1000) : null));
</script>
