<script setup lang="ts">
import { computed, onMounted, toRef, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Pencil } from '@lucide/vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { RecordActionMenu } from '@/Components/Patterns';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { enterLearningStep } from '@/Composables/useRecordTrail';
import LearningCompletionControl from '@/Features/Admin/Learning/LearningCompletionControl.vue';
import LearningStepAction from '@/Features/Admin/Learning/LearningStepAction.vue';
import LearningStepContent from '@/Features/Admin/Learning/LearningStepContent.vue';
import { useStepProgress } from '@/Features/Admin/Learning/composables/useStepProgress';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import { safeHref } from '@/Utils/safeHref';
import type { LearningStepData, LearningCoordinator, LearningInstitutionContacts, WikiPageSummary } from '@/Types/learning';

const props = defineProps<{
  step: LearningStepData;
  inPath: boolean;
  linkedPages?: Record<string, WikiPageSummary>;
  coordinators: LearningCoordinator[];
  institutionContacts: LearningInstitutionContacts[];
  canUpdate: boolean;
}>();
const readonly = computed(() => !props.inPath);
const { checked, saving, failed } = useStepProgress(toRef(props, 'step'), readonly);
const actions = computed<AdminRecordAction[]>(() => props.canUpdate ? [
  { key: 'edit', label: $t('Redaguoti'), icon: Pencil, href: route('learningSteps.edit', props.step.id) },
] : []);
const { formatDate } = useDateFormatter();
const hasAction = computed(() => Boolean(safeHref(props.step.actionUrl)));
onMounted(() => enterLearningStep({ id: props.step.id, title: props.step.title }));
watch(() => props.step.id, () => {
  enterLearningStep({ id: props.step.id, title: props.step.title });
});
</script>

<template>
  <OverviewPage :title="step.title" class="gap-8! lg:gap-10! sm:[&>[data-slot=overview-title-band]]:items-start">
    <template #heading>
      <div class="grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-3" data-slot="step-heading">
        <LearningCompletionControl v-model="checked" :step="step" :disabled="saving || !inPath || step.kind === 'meeting_prep'" class="mt-1.5 sm:mt-2.5" />
        <div class="min-w-0">
          <h1 class="text-balance text-3xl font-bold leading-tight text-foreground sm:text-4xl">{{ step.title }}</h1>
          <p class="mt-2 flex flex-wrap items-center gap-x-2 text-sm text-muted-foreground" data-slot="step-meta">
            <span :class="step.importance === 'essential' ? 'font-semibold text-brand' : 'font-semibold text-foreground'">{{ $t(`learning.importance.${step.importance}`) }}</span>
            <template v-if="step.stage">
              <span aria-hidden="true">·</span>
              <span>{{ $t(`learning.path.stages.${step.stage}`) }}</span>
            </template>
            <span aria-hidden="true">·</span>
            <span>{{ $t(`learning.kinds.${step.kind}`) }}</span>
          </p>
          <p v-if="failed" role="alert" class="mt-2 text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
        </div>
      </div>
    </template>
    <template v-if="hasAction || actions.length" #actions>
      <div class="flex flex-wrap items-center gap-2" data-slot="step-actions">
        <LearningStepAction :step="step" />
        <RecordActionMenu :actions="actions" trigger-size="compact" />
      </div>
    </template>

    <div class="grid gap-10 sm:pl-14 lg:grid-cols-[minmax(0,1fr)_17rem] lg:gap-x-14" data-slot="step-body">
      <article class="min-w-0 max-w-prose lg:col-start-1 lg:row-start-1">
        <LearningStepContent :step="step" :readonly="!inPath" :coordinators="coordinators" :institution-contacts="institutionContacts" :linked-pages="linkedPages" />
      </article>

      <aside
        class="space-y-5 border-t border-border pt-6 lg:sticky lg:top-[calc(var(--shell-chrome-height,0px)+1.5rem)] lg:col-start-2 lg:row-start-1 lg:self-start lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8"
        aria-labelledby="step-about-title"
        data-slot="step-about"
      >
        <h2 id="step-about-title" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.path.about_step') }}</h2>
        <p class="text-xs text-muted-foreground">
          {{ step.reviewedAt ? $t('learning.path.reviewed_on', { date: formatDate(step.reviewedAt) }) : $t('learning.path.not_reviewed') }}
        </p>
      </aside>
    </div>
  </OverviewPage>
</template>
