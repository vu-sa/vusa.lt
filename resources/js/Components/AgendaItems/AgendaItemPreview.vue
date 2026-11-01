<template>
  <PreviewPane
    entity-type="agenda_item"
    :eyebrow="$t('Darbotvarkės klausimas')"
    :title="item.title || $t('Be pavadinimo')"
    :href="showHref"
    :subtitle="item.meeting_title"
  >
    <template #badges>
      <StatusBadge v-if="decisionStatus" :status="decisionStatus" />
      <span v-if="item.brought_by_students" class="border border-border px-1.5 py-0.5 text-xs text-muted-foreground">
        {{ $t('Pateikė studentai') }}
      </span>
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti klausimą') }}
          </Link>
        </Button>
      </slot>
      <Button v-if="item.meeting_id" as-child variant="outline" size="sm" voice="sentence">
        <Link :href="route('meetings.show', item.meeting_id)">
          {{ $t('Atidaryti posėdį') }}
        </Link>
      </Button>
    </template>

    <PreviewFacts>
      <PreviewFact :label="$t('Tipas')">
        <AgendaItemTypeMark :type="item.type" />
      </PreviewFact>
      <PreviewFact v-if="institutionName" :label="$t('Institucija')">
        <Link v-if="item.institution_ids?.length" :href="route('institutions.show', item.institution_ids[0])" class="hover:text-brand">
          {{ institutionName }}
        </Link>
        <template v-else>
          {{ institutionName }}
        </template>
      </PreviewFact>
      <PreviewFact v-if="item.tenant_shortnames?.length" :label="$t('Padalinys')" :value="item.tenant_shortnames[0]" />
      <PreviewFact :label="$t('Data')" :value="meetingDate" tabular />
      <PreviewFact v-if="studentVote" :label="$t('Kaip balsavo studentai')">
        <StatusBadge :status="studentVote" />
      </PreviewFact>
      <PreviewFact v-if="studentBenefit" :label="$t('Palankumas studentams')">
        <StatusBadge :status="studentBenefit" />
      </PreviewFact>
    </PreviewFacts>

    <PreviewSection v-if="item.description" :title="$t('Aprašymas')">
      <p class="whitespace-pre-line text-sm text-muted-foreground">
        {{ item.description }}
      </p>
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import AgendaItemTypeMark from './AgendaItemTypeMark.vue';

import { getSearchTranslation } from '@/Composables/useTranslatedTitle';
import { PreviewFact, PreviewFacts, PreviewPane, PreviewSection, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { statusFor, studentBenefitStatuses, voteStatuses } from '@/Constants/statuses';
import type { AgendaItemSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  item: AgendaItemSearchResult;
}>();

const showHref = computed(() => route('agendaItems.show', props.item.id));
const institutionName = computed(() => getSearchTranslation(props.item, 'institution_name'));
const decisionStatus = computed(() => statusFor(voteStatuses, props.item.decision));
const studentVote = computed(() => statusFor(voteStatuses, props.item.student_vote));
const studentBenefit = computed(() => statusFor(studentBenefitStatuses, props.item.student_benefit));
// Search documents store Unix seconds.
const meetingDate = computed(() => (props.item.meeting_start_time ? formatDate(props.item.meeting_start_time * 1000) : null));
</script>
