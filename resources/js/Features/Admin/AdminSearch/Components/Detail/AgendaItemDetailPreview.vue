<template>
  <DetailLayout
    :icon="AgendaItemIcon"
    :kicker="$t('Darbotvarkės punktas')"
    :title="item.title || $t('Be pavadinimo')"
    :subtitle="item.meeting_title"
  >
    <template #badges>
      <Badge v-if="item.decision" :class="toneClass(voteTone(item.decision))">
        {{ getFacetValueLabel('decision', item.decision) }}
      </Badge>
      <Badge v-if="item.brought_by_students" variant="outline">
        {{ $t('Pateikė studentai') }}
      </Badge>
    </template>

    <template #actions>
      <Link :href="route('agendaItems.edit', item.id)">
        <Button size="sm">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti punktą') }}
        </Button>
      </Link>
      <Link v-if="item.meeting_id" :href="route('meetings.show', item.meeting_id)">
        <Button size="sm" variant="outline">
          <Eye class="mr-2 size-4" />
          {{ $t('Atidaryti posėdį') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow :label="$t('Posėdis')">
        <Link
          v-if="item.meeting_id"
          :href="route('meetings.show', item.meeting_id)"
          class="text-primary hover:underline"
        >
          {{ item.meeting_title || '—' }}
        </Link>
        <template v-else>{{ item.meeting_title || '—' }}</template>
      </DetailRow>
      <DetailRow v-if="institutionName" :label="$t('Institucija')">
        <Link
          v-if="item.institution_ids?.length"
          :href="route('institutions.show', item.institution_ids[0])"
          class="text-primary hover:underline"
        >
          {{ institutionName }}
        </Link>
        <template v-else>{{ institutionName }}</template>
      </DetailRow>
      <DetailRow v-if="item.tenant_shortnames?.length" :label="$t('Padalinys')" :value="item.tenant_shortnames[0]" />
      <DetailRow :label="$t('Data')" :value="formatSearchDate(item.meeting_start_time) || '—'" />
      <DetailRow v-if="item.student_vote" :label="$t('Kaip balsavo studentai')">
        <Badge :class="toneClass(voteTone(item.student_vote))">
          {{ getFacetValueLabel('student_vote', item.student_vote) }}
        </Badge>
      </DetailRow>
      <DetailRow v-if="item.student_benefit" :label="$t('Palankumas studentams')">
        <Badge :class="toneClass(voteTone(item.student_benefit))">
          {{ getFacetValueLabel('student_benefit', item.student_benefit) }}
        </Badge>
      </DetailRow>
    </div>

    <div v-if="item.description" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Aprašymas') }}
      </h3>
      <p class="whitespace-pre-line text-sm text-muted-foreground">{{ item.description }}</p>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';
import { toneClass, voteTone } from '../../Utils/searchBadges';
import { formatSearchDate } from '../../Utils/searchHitMappers';
import { getFacetValueLabel } from '../../Config/collectionFacetConfig';

import { AgendaItemIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { AgendaItemSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  item: AgendaItemSearchResult;
}>();

const institutionName = computed(() => props.item.institution_name_lt || props.item.institution_name_en);
</script>
