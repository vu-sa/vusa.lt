<script setup lang="ts">
import { computed, onMounted, ref, toRef, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { BookOpen, Pencil } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { RecordActionMenu, StatusBadge } from '@/Components/Patterns';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { enterLearningStep } from '@/Composables/useRecordTrail';
import { learningImportanceStatuses } from '@/Constants/statusIcons';
import LearningCompletionControl from '@/Features/Admin/Learning/LearningCompletionControl.vue';
import LearningStepAction from '@/Features/Admin/Learning/LearningStepAction.vue';
import LearningStepContent from '@/Features/Admin/Learning/LearningStepContent.vue';
import LearningStepGlossaryEditor, { type GlossaryTermOption } from '@/Features/Admin/Learning/LearningStepGlossaryEditor.vue';
import LearningTerms from '@/Features/Admin/Learning/LearningTerms.vue';
import { useStepProgress } from '@/Features/Admin/Learning/composables/useStepProgress';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import type { LearningStepData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

const props = defineProps<{
  step: LearningStepData;
  inPath: boolean;
  coordinators: LearningCoordinator[];
  institutionContacts: LearningInstitutionContacts[];
  canUpdate: boolean;
  glossaryOptions: GlossaryTermOption[];
}>();
const readonly = computed(() => !props.inPath);
const { checked, saving, failed } = useStepProgress(toRef(props, 'step'), readonly);
const glossaryOpen = ref(false);
const isMobile = useMediaQuery('(max-width: 767px)');
const actions = computed<AdminRecordAction[]>(() => props.canUpdate ? [
  { key: 'edit', label: $t('Redaguoti'), icon: Pencil, href: route('learningSteps.edit', props.step.id) },
  { key: 'glossary', label: $t('learning.path.manage_terms'), icon: BookOpen },
] : []);
const { formatDate } = useDateFormatter();
onMounted(() => enterLearningStep({ id: props.step.id, title: props.step.title }));
watch(() => props.step.id, () => {
  enterLearningStep({ id: props.step.id, title: props.step.title });
  glossaryOpen.value = false;
});
</script>

<template>
  <OverviewPage :title="step.title" class="gap-6! lg:gap-8!">
    <template #heading>
      <div class="flex items-start gap-3">
        <LearningCompletionControl v-model="checked" :step="step" :disabled="saving || !inPath || step.kind === 'meeting_prep'" class="mt-1" />
        <h1 class="text-2xl font-bold leading-tight text-foreground sm:text-3xl">{{ step.title }}</h1>
      </div>
    </template>
    <div class="space-y-4">
      <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted-foreground" data-slot="step-meta">
        <StatusBadge :status="learningImportanceStatuses[step.importance]" voice="sentence" />
        <span>· {{ $t(`learning.kinds.${step.kind}`) }}</span>
        <span v-if="step.audienceLabel">· {{ step.audienceLabel }}</span>
        <span>· {{ step.stage ? $t(`learning.path.stages.${step.stage}`) : $t('learning.path.meeting_prep') }}</span>
        <span>· {{ $t('learning.path.reviewed') }}: {{ step.reviewedAt ? formatDate(step.reviewedAt) : $t('learning.path.not_reviewed') }}</span>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <LearningStepAction :step="step" />
        <RecordActionMenu :actions="actions" @select="key => key === 'glossary' && (glossaryOpen = true)" />
      </div>
      <p v-if="failed" role="alert" class="text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
      <LearningStepContent :step="step" :readonly="!inPath" :coordinators="coordinators" :institution-contacts="institutionContacts" />
      <LearningTerms :terms="step.glossaryTerms" />
    </div>
    <section id="insights" class="scroll-mt-24 space-y-4 border-t border-border pt-6">
      <h2 class="text-xl font-bold">{{ $t('learning.path.insights') }}</h2>
      <DiscussionPanel :key="step.id" commentable-type="learningStep" :commentable-id="step.id" />
    </section>
    <Sheet v-if="canUpdate" v-model:open="glossaryOpen">
      <SheetContent :side="isMobile ? 'bottom' : 'right'" class="max-h-[92dvh] overflow-y-auto">
        <SheetHeader><SheetTitle>{{ $t('learning.path.manage_terms') }}</SheetTitle></SheetHeader>
        <div class="px-4 pb-6" data-slot="step-glossary-editor">
          <LearningStepGlossaryEditor :key="step.id" :step-id="step.id" :selected="step.glossaryTerms.map(term => ({ id: term.id, term: term.term }))" :options="glossaryOptions" />
        </div>
      </SheetContent>
    </Sheet>
  </OverviewPage>
</template>
