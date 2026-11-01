<template>
  <PreviewPane
    entity-type="institution"
    :eyebrow="$t('Institucija')"
    :title="name"
    :href="showHref"
    :subtitle="getSearchTranslation(institution, 'short_name')"
  >
    <template #mark>
      <StatusMark :icon="InstitutionIcon" :status="operatingStatus" />
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti instituciją') }}
          </Link>
        </Button>
      </slot>
    </template>

    <ContextFacts class="gap-x-6 gap-y-2 border-t-0 pt-0 text-sm">
      <ContextFact v-if="$slots.status" :label="$t('Būsena')">
        <slot name="status" />
      </ContextFact>
      <ContextFact v-if="institution.tenant_shortname" :label="$t('Padalinys')" :value="institution.tenant_shortname" />
      <ContextFact v-if="types.length" :label="$t('Tipai')" :value="types.join(', ')" />
      <ContextFact v-if="institution.email" :label="$t('El. paštas')" :value="institution.email" />
    </ContextFacts>

    <PreviewSection
      v-if="isLoading || data?.representatives?.length"
      :title="$t('Atstovai')"
      :loading="isLoading"
    >
      <RepresentativesList :users="data?.representatives ?? []" />
    </PreviewSection>

    <PreviewSection
      :title="$t('Paskutiniai posėdžiai')"
      :loading="isLoading"
      :empty="!data?.last_meetings?.length"
      :empty-message="$t('Nėra posėdžių')"
    >
      <ol class="divide-y divide-border border-y border-border">
        <li v-for="meeting in data?.last_meetings" :key="meeting.id" class="py-2">
          <Link
            :href="route('meetings.show', meeting.id)"
            class="flex items-start gap-3 text-sm transition-colors hover:text-brand pointer-coarse:min-h-11"
          >
            <span class="min-w-0 flex-1 break-words">{{ meeting.title || $t('Be pavadinimo') }}</span>
            <span v-if="meeting.start_time" class="mt-0.5 shrink-0 text-xs tabular-nums text-muted-foreground">
              {{ formatDate(meeting.start_time * 1000) }}
            </span>
          </Link>
          <MeetingOutcome v-if="meeting.outcome" :outcome="meeting.outcome" class="mt-1" />
        </li>
      </ol>
    </PreviewSection>
    <PreviewSection
      v-if="isLoading || related.length"
      :title="$t('Ryšiai')"
      :count="related.length"
      :loading="isLoading"
    >
      <PreviewChipList :items="related" :max="RELATED_LIMIT" :more-href="showHref" />
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { getSearchTranslation, getSearchTranslations } from '@/Composables/useTranslatedTitle';
import RepresentativesList from '@/Components/Avatars/RepresentativesList.vue';
import MeetingOutcome from '@/Components/Meetings/MeetingOutcome.vue';
import { InstitutionIcon } from '@/Components/icons';
import { ContextFact, ContextFacts, PreviewChipList, PreviewPane, PreviewSection, StatusMark, type PreviewChipItem } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { usePreviewApi } from '@/Composables/usePreviewApi';
import { operatingStatuses } from '@/Constants/statuses';
import type { InstitutionSearchResult, MeetingSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

interface InstitutionPreviewData {
  types: Array<{ id: string; title: string }>;
  /** `outcome` is null for a meeting this viewer may not open. */
  last_meetings: Array<{ id: string; title: string; start_time: number | null; outcome: Partial<MeetingSearchResult> | null }>;
  representatives: Array<{ id: string; name: string; profile_photo_path: string | null }>;
  related_institutions: Array<{ id: string; name: string; kind_label?: string }>;
}

/** Some institutions have dozens of relations; the record page lists them all. */
const RELATED_LIMIT = 6;

const props = defineProps<{
  institution: InstitutionSearchResult;
}>();

const { data, isLoading } = usePreviewApi<InstitutionPreviewData>(route('api.v1.admin.institutions.preview', props.institution.id));

const name = computed(() => getSearchTranslation(props.institution, 'name') || $t('Be pavadinimo'));
const showHref = computed(() => route('institutions.show', props.institution.id));
const operatingStatus = computed(() => operatingStatuses[props.institution.is_active === false || props.institution.is_active === 0 ? 'inactive' : 'active']);

// The search document already carries the type titles; the fetched ones replace them when they arrive.
const types = computed(() => data.value?.types?.map(type => type.title) ?? getSearchTranslations(props.institution, 'type_titles'));

const related = computed<PreviewChipItem[]>(() => (data.value?.related_institutions ?? []).map(institution => ({
  id: institution.id,
  name: institution.name,
  meta: institution.kind_label,
  href: route('institutions.show', institution.id),
})));
</script>
