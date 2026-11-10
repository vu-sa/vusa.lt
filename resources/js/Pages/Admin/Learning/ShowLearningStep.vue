<script setup lang="ts">
import { computed, onMounted, ref, toRef, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, BookOpen, Pencil } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { RecordActionMenu } from '@/Components/Patterns';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { enterLearningStep } from '@/Composables/useRecordTrail';
import LearningCompletionControl from '@/Features/Admin/Learning/LearningCompletionControl.vue';
import LearningStepAction from '@/Features/Admin/Learning/LearningStepAction.vue';
import LearningStepContent from '@/Features/Admin/Learning/LearningStepContent.vue';
import LearningStepGlossaryEditor, { type GlossaryTermOption } from '@/Features/Admin/Learning/LearningStepGlossaryEditor.vue';
import LearningTerms from '@/Features/Admin/Learning/LearningTerms.vue';
import { useStepProgress } from '@/Features/Admin/Learning/composables/useStepProgress';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import { safeHref } from '@/Utils/safeHref';
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
const hasAction = computed(() => Boolean(safeHref(props.step.kind === 'knowledge' ? props.step.source?.url : props.step.actionUrl)));
const sourceHref = computed(() => safeHref(props.step.source?.url));
const sourceLabel = computed(() => {
  if (!props.step.source) return null;
  if (props.step.source.title) return props.step.source.title;
  try {
    return sourceHref.value ? new URL(sourceHref.value).hostname.replace(/^www\./, '') : null;
  }
  catch {
    return sourceHref.value ?? null;
  }
});
onMounted(() => enterLearningStep({ id: props.step.id, title: props.step.title }));
watch(() => props.step.id, () => {
  enterLearningStep({ id: props.step.id, title: props.step.title });
  glossaryOpen.value = false;
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
        <RecordActionMenu :actions="actions" trigger-size="compact" @select="key => key === 'glossary' && (glossaryOpen = true)" />
      </div>
    </template>

    <div class="grid gap-10 sm:pl-14 lg:grid-cols-[minmax(0,1fr)_17rem] lg:gap-x-14" data-slot="step-body">
      <article class="min-w-0 max-w-prose lg:col-start-1 lg:row-start-1">
        <LearningStepContent :step="step" :readonly="!inPath" :coordinators="coordinators" :institution-contacts="institutionContacts" />
      </article>

      <aside
        class="space-y-5 border-t border-border pt-6 lg:sticky lg:top-[calc(var(--shell-chrome-height,0px)+1.5rem)] lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-start lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8"
        aria-labelledby="step-about-title"
        data-slot="step-about"
      >
        <h2 id="step-about-title" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.path.about_step') }}</h2>
        <div v-if="sourceLabel" class="space-y-1">
          <p class="text-xs text-muted-foreground">{{ $t('learning.path.source') }}</p>
          <a v-if="sourceHref" :href="sourceHref" target="_blank" rel="noopener noreferrer" class="group inline-flex items-start gap-1.5 text-sm font-semibold leading-snug text-foreground hover:text-brand">
            <span class="underline decoration-border underline-offset-4 group-hover:decoration-brand">{{ sourceLabel }}</span>
            <ArrowUpRight class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
          </a>
          <p v-else class="text-sm font-semibold leading-snug">{{ sourceLabel }}</p>
          <p v-if="step.source?.type === 'document' && !sourceHref" class="text-xs text-muted-foreground">{{ $t('learning.path.document_private') }}</p>
        </div>
        <div v-if="step.section" class="space-y-1">
          <p class="text-xs text-muted-foreground">{{ $t('learning_library.fields.section') }}</p>
          <p class="text-sm font-semibold">{{ step.section.title }}</p>
        </div>
        <LearningTerms :terms="step.glossaryTerms" />
        <p class="text-xs text-muted-foreground">
          {{ step.reviewedAt ? $t('learning.path.reviewed_on', { date: formatDate(step.reviewedAt) }) : $t('learning.path.not_reviewed') }}
        </p>
      </aside>

      <section id="insights" class="min-w-0 scroll-mt-24 border-t border-border pt-6 lg:col-start-1 lg:row-start-2" aria-labelledby="insights-title">
        <h2 id="insights-title" class="sr-only">{{ $t('learning.path.insights') }}</h2>
        <DiscussionPanel :key="step.id" commentable-type="learningStep" :commentable-id="step.id" />
      </section>
    </div>

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
