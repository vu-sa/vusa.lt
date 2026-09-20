<template>
  <DetailLayout
    :icon="MeetingIcon"
    :kicker="$t('Posėdis')"
    :title="meeting.title || $t('Be pavadinimo')"
    :subtitle="institutionName"
  >
    <template #badges>
      <StatusBadge v-if="completionStatus" :status="completionStatus" />
      <StatusBadge v-if="alignmentStatus" :status="alignmentStatus" />
    </template>

    <template #actions>
      <Link :href="route('meetings.show', meeting.id)">
        <Button size="sm">
          <Eye class="mr-2 size-4" />
          {{ $t('Atidaryti posėdį') }}
        </Button>
      </Link>
      <Link :href="route('meetings.edit', meeting.id)">
        <Button size="sm" variant="outline">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <!-- Facts -->
    <div class="divide-y divide-border border border-border px-4">
      <DetailRow :label="$t('Institucija')">
        <Link
          v-if="meeting.institution_ids?.length"
          :href="route('institutions.show', meeting.institution_ids[0])"
          class="text-primary hover:underline"
        >
          {{ institutionName || '—' }}
        </Link>
        <template v-else>
          {{ institutionName || '—' }}
        </template>
      </DetailRow>
      <DetailRow v-if="meeting.tenant_shortname" :label="$t('Padalinys')" :value="meeting.tenant_shortname" />
      <DetailRow :label="$t('Data')" :value="formatSearchDate(meeting.start_time) || '—'" />
      <DetailRow :label="$t('Punktai')" :value="agendaCount" />
    </div>

    <!-- Representatives -->
    <div v-if="data?.representatives?.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Atstovai') }}
      </h3>
      <UsersAvatarGroup :users="data.representatives" :max="10" :size="32" clickable />
    </div>

    <!-- Agenda -->
    <div class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Darbotvarkė') }}
      </h3>

      <div v-if="isFetching && !data" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-9 animate-pulse bg-muted/50" />
      </div>

      <ol v-else-if="data?.agenda_items?.length" class="space-y-1.5">
        <li v-for="(item, index) in data.agenda_items" :key="item.id">
          <Link
            :href="route('agendaItems.show', item.id)"
            class="group flex items-start gap-3 border border-border bg-card px-3 py-2 transition-colors hover:bg-secondary"
          >
            <span class="mt-0.5 text-xs tabular-nums text-muted-foreground">{{ index + 1 }}</span>
            <span class="min-w-0 flex-1 whitespace-normal break-words text-sm group-hover:text-foreground">{{ item.title }}</span>
            <StatusBadge v-if="decisionStatus(item.decision)" :status="decisionStatus(item.decision)!" class="mt-0.5 shrink-0" />
          </Link>
        </li>
      </ol>

      <p v-else class="text-sm text-muted-foreground">
        {{ $t('Nėra darbotvarkės punktų') }}
      </p>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, Pencil, Vote } from 'lucide-vue-next';

import { formatSearchDate } from '../../Utils/searchHitMappers';
import { getFacetValueLabel } from '../../Config/collectionFacetConfig';

import DetailRow from './DetailRow.vue';
import DetailLayout from './DetailLayout.vue';

import { MeetingIcon } from '@/Components/icons';
import { StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { useApi } from '@/Composables/useApi';
import {
  meetingCompletionStatuses,
  voteStatuses,
  type MeetingCompletionStatus,
  type StatusPresentation,
} from '@/Constants/statuses';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import type { VoteValue } from '@/Types/enums';

interface MeetingPreviewData {
  institutions: Array<{ id: string; name: string }>;
  agenda_items: Array<{ id: string; title: string; decision: string | null; student_benefit: string | null }>;
  representatives: Array<{ id: string; name: string; profile_photo_path: string | null }>;
}

const props = defineProps<{
  meeting: MeetingSearchResult;
}>();

// Remounted per selection (parent keys by hit id), so an immediate fetch is fresh each time.
const { data, isFetching } = useApi<MeetingPreviewData>(
  route('api.v1.admin.meetings.preview', props.meeting.id),
  { immediate: true, showErrorToast: false },
);

const institutionName = computed(() => props.meeting.institution_name_lt || props.meeting.institution_name_en);

// One name per state everywhere (U10): the same maps the collection row and the record page draw from.
const completionStatus = computed<StatusPresentation | null>(() => {
  const value = props.meeting.completion_status;

  return value && value in meetingCompletionStatuses ? meetingCompletionStatuses[value as MeetingCompletionStatus] : null;
});

// No U10 map exists for vote alignment yet, so it is named neutrally rather than given an invented colour.
const alignmentStatus = computed<StatusPresentation | null>(() => {
  const value = props.meeting.vote_alignment_status;

  return value
    ? { label: getFacetValueLabel('vote_alignment_status', value), role: 'neutral', icon: Vote }
    : null;
});

function decisionStatus(decision: string | null): StatusPresentation | null {
  return decision && decision in voteStatuses ? voteStatuses[decision as VoteValue] : null;
}

const agendaCount = computed(() => {
  if (data.value?.agenda_items) {
    return String(data.value.agenda_items.length);
  }
  return props.meeting.agenda_items_count != null ? String(props.meeting.agenda_items_count) : '—';
});

</script>
