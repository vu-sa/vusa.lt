<template>
  <PreviewPane
    entity-type="institution"
    :eyebrow="$t('Institucija')"
    :title="name"
    :href="showHref"
    :subtitle="getSearchTranslation(institution, 'short_name')"
  >
    <template #badges>
      <!-- A page that passes its own status control already says whether the institution is active. -->
      <InactiveInstitutionBadge v-if="!$slots.status" :active="institution.is_active" />
      <span v-if="institution.tenant_shortname" class="border border-border px-1.5 py-0.5 text-xs text-muted-foreground">{{ institution.tenant_shortname }}</span>
    </template>

    <template v-if="$slots.status" #status>
      <slot name="status" />
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

    <PreviewFacts>
      <PreviewFact :label="$t('El. paštas')" :value="institution.email" />
      <PreviewFact :label="$t('Tipai')" :value="types.join(', ')" />
    </PreviewFacts>

    <PreviewSection
      v-if="isLoading || data?.representatives?.length"
      :title="$t('Atstovai')"
      :loading="isLoading"
    >
      <UsersAvatarGroup :users="data?.representatives ?? []" :max="10" :size="32" clickable />
    </PreviewSection>

    <PreviewSection
      v-if="isLoading || related.length"
      :title="$t('Ryšiai')"
      :count="related.length"
      :loading="isLoading"
    >
      <PreviewChipList :items="related" :max="RELATED_LIMIT" :more-href="showHref" />
    </PreviewSection>

    <PreviewSection
      :title="$t('Paskutiniai posėdžiai')"
      :loading="isLoading"
      :empty="!data?.last_meetings?.length"
      :empty-message="$t('Nėra posėdžių')"
    >
      <ol class="divide-y divide-border border-y border-border">
        <li v-for="meeting in data?.last_meetings" :key="meeting.id">
          <Link
            :href="route('meetings.show', meeting.id)"
            class="flex items-start gap-3 py-2 text-sm transition-colors hover:text-brand pointer-coarse:min-h-11"
          >
            <span class="min-w-0 flex-1 break-words">{{ meeting.title || $t('Be pavadinimo') }}</span>
            <span v-if="meeting.start_time" class="mt-0.5 shrink-0 text-xs tabular-nums text-muted-foreground">
              {{ formatDate(meeting.start_time * 1000) }}
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

import { getSearchTranslation, getSearchTranslations } from '@/Composables/useTranslatedTitle';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { PreviewChipList, PreviewFact, PreviewFacts, PreviewPane, PreviewSection, type PreviewChipItem } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { usePreviewApi } from '@/Composables/usePreviewApi';
import type { InstitutionSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

interface InstitutionPreviewData {
  types: Array<{ id: string; title: string }>;
  last_meetings: Array<{ id: string; title: string; start_time: number | null }>;
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

// The search document already carries the type titles; the fetched ones replace them when they arrive.
const types = computed(() => data.value?.types?.map(type => type.title) ?? getSearchTranslations(props.institution, 'type_titles'));

const related = computed<PreviewChipItem[]>(() => (data.value?.related_institutions ?? []).map(institution => ({
  id: institution.id,
  name: institution.name,
  meta: institution.kind_label,
  href: route('institutions.show', institution.id),
})));
</script>
