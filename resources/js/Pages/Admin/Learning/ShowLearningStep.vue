<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import RecordPage, { type RecordFact } from '@/Components/Layouts/RecordPage.vue';
import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { learningImportanceStatuses, learningProgressStatuses } from '@/Constants/statusIcons';
import LearningStepContent from '@/Features/Admin/Learning/LearningStepContent.vue';
import LearningTabs from '@/Features/Admin/Learning/LearningTabs.vue';
import type { LearningStepData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

const props = defineProps<{
  step: LearningStepData;
  inPath: boolean;
  coordinators: LearningCoordinator[];
  institutionContacts: LearningInstitutionContacts[];
  canSeeReps: boolean;
}>();
const { formatDate } = useDateFormatter();
const sections = computed(() => [
  { value: 'about', label: $t('learning.path.about') },
  { value: 'insights', label: $t('learning.path.insights'), count: props.step.insightsCount },
]);
const facts = computed<RecordFact[]>(() => [
  { key: 'kind', label: $t('learning.path.kind'), value: $t(`learning.kinds.${props.step.kind}`) },
  { key: 'layer', label: $t('learning.path.layer'), value: props.step.layerLabel },
  { key: 'stage', label: $t('learning.path.stage'), value: props.step.stage ? $t(`learning.path.stages.${props.step.stage}`) : $t('learning.path.meeting_prep') },
  { key: 'importance', label: $t('learning.path.importance'), value: $t(`learning.importance.${props.step.importance}`) },
  { key: 'source', label: $t('learning.path.source'), value: props.step.source?.title ?? (props.step.source ? $t(`learning.path.source_${props.step.source.type}`) : '—'), href: props.step.source?.url ?? undefined, external: true },
  { key: 'reviewed', label: $t('learning.path.reviewed'), value: props.step.reviewedAt ? formatDate(props.step.reviewedAt) : $t('learning.path.not_reviewed') },
]);
</script>

<template>
  <div>
    <LearningTabs :can-see-reps="canSeeReps" />
    <RecordPage :key="step.id" :title="step.title" entity-type="learning_step" title-voice="sentence" :facts="facts" :sections="sections">
      <template #subtitle>
        <StatusBadge v-if="inPath" :status="learningProgressStatuses[step.state]" voice="sentence" />
      </template>
      <template #fact-importance>
        <StatusBadge :status="learningImportanceStatuses[step.importance]" voice="sentence" />
      </template>
      <template #about>
        <LearningStepContent :step="step" :readonly="!inPath" :coordinators="coordinators" :institution-contacts="institutionContacts" />
        <div v-if="step.glossaryTerms.length" class="mt-6 space-y-3">
          <h3 class="text-sm font-semibold">{{ $t('learning.path.terms') }}</h3>
          <div class="flex flex-wrap gap-2">
            <Popover v-for="term in step.glossaryTerms" :key="term.id">
              <PopoverTrigger as-child>
                <Button variant="outline" voice="sentence" class="h-auto min-h-11 max-w-full whitespace-normal">{{ term.term }}</Button>
              </PopoverTrigger>
              <PopoverContent class="max-w-[calc(100vw-2rem)]">
                <p class="font-semibold">{{ term.term }}</p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ term.definition }}</p>
              </PopoverContent>
            </Popover>
          </div>
        </div>
      </template>
      <template #insights>
        <DiscussionPanel :key="step.id" commentable-type="learningStep" :commentable-id="step.id" />
      </template>
    </RecordPage>
  </div>
</template>
