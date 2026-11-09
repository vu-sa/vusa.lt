<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { ChevronDown, RefreshCw } from '@lucide/vue';

import EmptyState from '@/Components/Patterns/EmptyState.vue';
import type { LearningCoordinator, LearningInstitutionContacts, LearningPathData, LearningStepData } from '@/Types/learning';

import LearningStepRow from './LearningStepRow.vue';

const props = defineProps<{
  path: LearningPathData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
}>();
const opened = ref<Record<string, boolean>>({});
watch(() => props.path.currentStage, stage => {
  const initial = stage ?? props.path.stages.find(station => station.steps.some(step => step.state === 'pending' || step.state === 'updated'))?.stage;
  if (initial) opened.value[initial] = true;
}, { immediate: true });
const hasSteps = computed(() => props.path.stages.some(stage => stage.steps.length));
const done = (steps: LearningStepData[]) => steps.filter(step => step.state === 'done' || step.state === 'skipped').length;
function groups(steps: LearningStepData[]) {
  const layers = new Map<string, LearningStepData[]>();
  for (const step of steps) {
    const group = layers.get(step.layerLabel) ?? [];
    group.push(step);
    layers.set(step.layerLabel, group);
  }
  return [...layers].map(([label, items]) => ({ label, steps: items }));
}
function toggle(stage: string, event: Event) {
  opened.value[stage] = (event.target as HTMLDetailsElement).open;
}
</script>

<template>
  <div class="space-y-10" data-slot="learning-path">
    <ol v-if="hasSteps" class="ml-2 border-l border-border pl-5 sm:pl-7">
      <li v-for="station in path.stages" :key="station.stage" class="relative pb-6 last:pb-0">
        <span :class="['absolute -left-[1.6rem] top-4 size-3 border-2 sm:-left-[2.1rem]', path.currentStage === station.stage ? 'border-foreground bg-background' : 'border-border bg-background']" aria-hidden="true" />
        <details :open="opened[station.stage] ?? false" :data-stage="station.stage" @toggle="toggle(station.stage, $event)">
          <summary class="flex min-h-11 cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 py-2 focus-visible:outline-2 focus-visible:outline-ring">
            <h2 class="text-base font-semibold">{{ $t(`learning.path.stages.${station.stage}`) }}</h2>
            <span v-if="path.currentStage === station.stage" class="border border-border px-2 py-1 text-xs text-muted-foreground" data-slot="current-stage" aria-current="step">{{ $t('learning.path.now') }}</span>
            <span class="ml-auto text-xs tabular-nums text-muted-foreground">{{ done(station.steps) }}/{{ station.steps.length }}</span>
            <ChevronDown class="size-4 text-muted-foreground" aria-hidden="true" />
          </summary>
          <div v-for="group in groups(station.steps)" :key="group.label" class="pt-4">
            <h3 class="border-b border-border pb-2 text-xs font-bold uppercase tracking-wide text-muted-foreground">{{ group.label }}</h3>
            <LearningStepRow v-for="step in group.steps" :key="step.id" :step="step" :readonly="readonly" :coordinators="coordinators" :institution-contacts="institutionContacts" />
          </div>
        </details>
      </li>
    </ol>
    <EmptyState v-else :title="$t('learning.path.empty_title')" :description="$t('learning.path.empty_description')" />

    <section v-if="path.meetingPrep.length" class="space-y-5 border-t border-border pt-6" data-slot="meeting-prep">
      <h2 class="flex items-center gap-2 text-lg font-semibold"><RefreshCw class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />{{ $t('learning.path.meeting_prep') }}</h2>
      <div v-for="group in path.meetingPrep" :key="group.institutionType.id" class="space-y-2">
        <h3 class="text-sm font-semibold">{{ group.institutionType.title }}</h3>
        <ul class="list-disc space-y-3 pl-5">
          <li v-for="step in group.steps" :key="step.id">
            <p class="text-sm font-medium">{{ step.title }}</p>
            <p v-if="step.description" class="mt-1 max-w-prose whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ step.description }}</p>
          </li>
        </ul>
      </div>
    </section>
  </div>
</template>
