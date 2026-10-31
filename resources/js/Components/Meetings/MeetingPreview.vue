<template>
  <PreviewPane
    entity-type="meeting"
    :eyebrow="$t('Posėdis')"
    :title="meeting.title || $t('Be pavadinimo')"
    :href="showHref"
    :subtitle="institutionName"
  >
    <template #badges>
      <InactiveInstitutionBadge v-if="meeting.inactive_institution_ids?.length" :active="false" />
      <StatusBadge v-if="completionStatus" :status="completionStatus" />
      <StatusBadge v-if="alignmentStatus" :status="alignmentStatus" />
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti posėdį') }}
          </Link>
        </Button>
      </slot>
      <Button v-if="meeting.institution_ids?.length" as-child variant="outline" size="sm" voice="sentence">
        <Link :href="route('institutions.show', meeting.institution_ids[0])">
          {{ $t('Atidaryti instituciją') }}
        </Link>
      </Button>
    </template>

    <PreviewFacts>
      <PreviewFact :label="$t('Institucija')">
        <Link v-if="meeting.institution_ids?.length" :href="route('institutions.show', meeting.institution_ids[0])" class="hover:text-brand">
          {{ institutionName || '—' }}
        </Link>
        <template v-else>
          {{ institutionName || '—' }}
        </template>
      </PreviewFact>
      <PreviewFact v-if="meeting.tenant_shortname" :label="$t('Padalinys')" :value="meeting.tenant_shortname" />
      <PreviewFact :label="$t('Data')" :value="startsAt" tabular />
      <PreviewFact :label="$t('Punktai')" :value="agendaCount" tabular />
    </PreviewFacts>

    <PreviewSection
      v-if="isLoading || data?.representatives?.length"
      :title="$t('Atstovai')"
      :loading="isLoading"
    >
      <UsersAvatarGroup :users="data?.representatives ?? []" :max="10" :size="32" clickable />
    </PreviewSection>

    <PreviewSection
      :title="$t('Darbotvarkė')"
      :loading="isLoading"
      :empty="!data?.agenda_items?.length"
      :empty-message="$t('Nėra darbotvarkės punktų')"
    >
      <ol class="divide-y divide-border border-y border-border">
        <li v-for="(item, index) in data?.agenda_items" :key="item.id">
          <Link
            :href="route('agendaItems.show', item.id)"
            class="flex items-start gap-3 py-2 transition-colors hover:text-brand pointer-coarse:min-h-11"
          >
            <span class="mt-0.5 text-xs tabular-nums text-muted-foreground">{{ index + 1 }}</span>
            <span class="min-w-0 flex-1 break-words text-sm">{{ item.title }}</span>
            <StatusBadge v-if="statusFor(voteStatuses, item.decision)" :status="statusFor(voteStatuses, item.decision)!" class="mt-0.5 shrink-0" />
          </Link>
        </li>
      </ol>
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Vote } from '@lucide/vue';
import { computed } from 'vue';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { PreviewFact, PreviewFacts, PreviewPane, PreviewSection, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { usePreviewApi } from '@/Composables/usePreviewApi';
import { meetingCompletionStatuses, statusFor, voteStatuses, type StatusPresentation } from '@/Constants/statuses';
import { getFacetValueLabel } from '@/Features/Admin/AdminSearch/Config/collectionFacetConfig';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import { formatDateTime } from '@/Utils/dateTime';

interface MeetingPreviewData {
  agenda_items: Array<{ id: string; title: string; decision: string | null }>;
  representatives: Array<{ id: string; name: string; profile_photo_path: string | null }>;
}

const props = defineProps<{
  meeting: MeetingSearchResult;
}>();

const { data, isLoading } = usePreviewApi<MeetingPreviewData>(route('api.v1.admin.meetings.preview', props.meeting.id));

const showHref = computed(() => route('meetings.show', props.meeting.id));
const institutionName = computed(() => getSearchTranslation(props.meeting, 'institution_name'));
const completionStatus = computed(() => statusFor(meetingCompletionStatuses, props.meeting.completion_status));

// No status map exists for vote alignment yet, so it is named neutrally rather than given an invented colour.
const alignmentStatus = computed<StatusPresentation | null>(() => {
  const value = props.meeting.vote_alignment_status;

  return value ? { label: getFacetValueLabel('vote_alignment_status', value), role: 'neutral', icon: Vote } : null;
});

// Search documents store Unix seconds.
const startsAt = computed(() => (props.meeting.start_time ? formatDateTime(props.meeting.start_time * 1000) : null));
const agendaCount = computed(() => data.value?.agenda_items.length ?? props.meeting.agenda_items_count ?? null);
</script>
