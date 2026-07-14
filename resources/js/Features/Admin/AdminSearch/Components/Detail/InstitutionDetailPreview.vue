<template>
  <DetailLayout
    :icon="InstitutionIcon"
    :kicker="$t('Institucija')"
    :title="institution.name_lt || institution.name_en || $t('Be pavadinimo')"
    :subtitle="institution.short_name_lt || institution.short_name_en"
  >
    <template v-if="institution.tenant_shortname" #badges>
      <Badge variant="outline">
        {{ institution.tenant_shortname }}
      </Badge>
    </template>

    <template #actions>
      <Link :href="route('institutions.show', institution.id)">
        <Button size="sm">
          <Eye class="mr-2 size-4" />
          {{ $t('Peržiūrėti') }}
        </Button>
      </Link>
      <Link :href="route('institutions.edit', institution.id)">
        <Button size="sm" variant="outline">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow v-if="institution.email" :label="$t('El. paštas')" :value="institution.email" />
      <DetailRow :label="$t('Padalinys')" :value="institution.tenant_shortname || '—'" />
    </div>

    <!-- Types -->
    <div v-if="types.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Tipai') }}
      </h3>
      <div class="flex flex-wrap gap-1.5">
        <Badge v-for="type in types" :key="type.id" variant="secondary">
          {{ type.title }}
        </Badge>
      </div>
    </div>

    <!-- Representatives -->
    <div v-if="data?.representatives?.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Atstovai') }}
      </h3>
      <UsersAvatarGroup :users="data.representatives" :max="REPRESENTATIVE_LIMIT" :size="32" clickable />
    </div>

    <!-- Related institutions (one-deep graph) -->
    <div v-if="data?.related_institutions?.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Ryšiai') }}
      </h3>
      <InstitutionRelationGraph
        :center-name="institution.name_lt || institution.name_en || $t('Be pavadinimo')"
        :related="data.related_institutions"
      />
    </div>

    <!-- Recent meetings -->
    <div class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Paskutiniai posėdžiai') }}
      </h3>

      <div v-if="isFetching && !data" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-9 animate-pulse rounded-md bg-muted/50" />
      </div>

      <ol v-else-if="data?.last_meetings?.length" class="space-y-1.5">
        <li v-for="meeting in data.last_meetings" :key="meeting.id">
          <Link
            :href="route('meetings.show', meeting.id)"
            :class="['group flex items-start gap-3 rounded-md border bg-card px-3 py-2', interactiveCardClass]"
          >
            <span class="min-w-0 flex-1 whitespace-normal break-words text-sm group-hover:text-foreground">
              {{ meeting.title || $t('Be pavadinimo') }}
            </span>
            <span v-if="meeting.start_time" class="mt-0.5 shrink-0 text-xs tabular-nums text-muted-foreground">
              {{ formatSearchDate(meeting.start_time) }}
            </span>
          </Link>
        </li>
      </ol>

      <p v-else class="text-sm text-muted-foreground">
        {{ $t('Nėra posėdžių') }}
      </p>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, Pencil } from 'lucide-vue-next';

import { formatSearchDate } from '../../Utils/searchHitMappers';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';
import InstitutionRelationGraph from './InstitutionRelationGraph.vue';

import { interactiveCardClass } from '@/Utils/interactiveCard';
import { InstitutionIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { useApi } from '@/Composables/useApi';
import type { InstitutionSearchResult } from '@/Shared/Search/types';

interface InstitutionPreviewData {
  types: Array<{ id: string; title: string }>;
  last_meetings: Array<{ id: string; title: string; start_time: number | null }>;
  representatives: Array<{ id: string; name: string; profile_photo_path: string | null }>;
  related_institutions: Array<{ id: string; name: string; direction: string; type: string; authorized: boolean }>;
}

/** Soft cap on representative avatars before collapsing into a "+N" indicator. */
const REPRESENTATIVE_LIMIT = 10;

const props = defineProps<{
  institution: InstitutionSearchResult;
}>();

// Remounted per selection (parent keys by hit id), so an immediate fetch is fresh each time.
const { data, isFetching } = useApi<InstitutionPreviewData>(
  route('api.v1.admin.institutions.preview', props.institution.id),
  { immediate: true, showErrorToast: false },
);

// Prefer freshly fetched types; fall back to the titles carried in the search document.
const types = computed<Array<{ id: string; title: string }>>(() => {
  if (data.value?.types?.length) {
    return data.value.types;
  }
  return (props.institution.type_titles ?? []).map((title, index) => ({ id: String(index), title }));
});

</script>
