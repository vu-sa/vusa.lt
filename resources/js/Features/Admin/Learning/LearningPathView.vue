<script setup lang="ts">
import { computed } from 'vue';
import { Check, RefreshCw } from '@lucide/vue';

import EmptyState from '@/Components/Patterns/EmptyState.vue';
import type { LearningCoordinator, LearningInstitutionContacts, LearningPathData, LearningStepData } from '@/Types/learning';

import LearningStepRow from './LearningStepRow.vue';
import LearningDocumentsEntry from './LearningDocumentsEntry.vue';
import LearningAudienceNotes from './LearningAudienceNotes.vue';

const props = defineProps<{
  path: LearningPathData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
}>();

const stations = computed(() => props.path.stages.filter(station => station.steps.length));
const currentIndex = computed(() => stations.value.findIndex(station => station.stage === props.path.currentStage));
const hasSteps = computed(() => stations.value.length > 0);

const resolved = (steps: LearningStepData[]) => steps.filter(step => step.state === 'done' || step.state === 'skipped').length;

function stateOf(index: number, steps: LearningStepData[]): 'done' | 'now' | 'behind' | 'ahead' {
  if (resolved(steps) === steps.length) return 'done';
  if (index === currentIndex.value) return 'now';
  return index < currentIndex.value ? 'behind' : 'ahead';
}

function groups(steps: LearningStepData[]) {
  const audiences = new Map<string, LearningStepData[]>();
  for (const step of steps) {
    const rows = audiences.get(step.audienceLabel) ?? [];
    rows.push(step);
    audiences.set(step.audienceLabel, rows);
  }
  return [...audiences].map(([label, steps]) => ({ label, steps }));
}
</script>

<template>
  <div class="space-y-12" data-slot="learning-path">
    <LearningDocumentsEntry :documents="path.documents" :readonly="readonly" />

    <ol v-if="hasSteps" class="relative">
      <li
        v-for="(station, index) in stations"
        :key="station.stage"
        :data-stage="station.stage"
        class="relative grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-3 pb-10 last:pb-0 sm:grid-cols-[3rem_minmax(0,1fr)] sm:gap-x-6"
      >
        <span
          v-if="index < stations.length - 1"
          aria-hidden="true"
          :class="[
            'absolute bottom-0 left-5 top-10 hidden w-0.5 sm:block -translate-x-1/2 sm:left-6 sm:top-12',
            index < currentIndex ? 'bg-brand-fill' : 'bg-border',
          ]"
        />

        <span
          aria-hidden="true"
          :class="[
            'relative z-10 col-start-1 row-start-1 flex size-10 items-center justify-center border-2 text-base font-bold tabular-nums sm:size-12 sm:text-lg',
            stateOf(index, station.steps) === 'done' && 'border-foreground bg-foreground text-background',
            stateOf(index, station.steps) === 'now' && 'border-brand-fill bg-brand-fill text-brand-foreground',
            stateOf(index, station.steps) === 'behind' && 'border-foreground bg-background text-foreground',
            stateOf(index, station.steps) === 'ahead' && 'border-border bg-background text-muted-foreground',
          ]"
        >
          <Check v-if="stateOf(index, station.steps) === 'done'" class="size-5" />
          <template v-else>{{ index + 1 }}</template>
        </span>

        <section class="col-span-2 col-start-1 row-start-1 min-w-0 sm:col-span-1 sm:col-start-2" :aria-labelledby="`stage-${station.stage}`">
          <header class="flex min-h-10 flex-wrap pl-13 sm:pl-0 items-center gap-x-4 gap-y-2 sm:min-h-12">
            <h2 :id="`stage-${station.stage}`" class="text-xl font-bold leading-tight text-foreground sm:text-2xl">
              {{ $t(`learning.path.stages.${station.stage}`) }}
            </h2>
            <span
              v-if="path.currentStage === station.stage"
              class="border border-brand-fill px-2 py-0.5 text-xs font-semibold text-brand"
              data-slot="current-stage"
              aria-current="step"
            >{{ $t('learning.path.now') }}</span>
            <span class="ml-auto inline-flex items-center gap-3 text-sm tabular-nums text-muted-foreground">
              <span class="hidden h-1.5 w-24 bg-muted sm:block" aria-hidden="true">
                <span class="block h-full bg-foreground" :style="{ width: `${(resolved(station.steps) / station.steps.length) * 100}%` }" />
              </span>
              {{ $t('learning.path.stage_progress', { done: String(resolved(station.steps)), total: String(station.steps.length) }) }}
            </span>
          </header>
          <p class="mt-1 max-w-prose pl-13 text-sm text-muted-foreground sm:pl-0">{{ $t(`learning.path.stage_hints.${station.stage}`) }}</p>

          <div v-for="group in groups(station.steps)" :key="group.label" class="mt-4">
            <h3 class="flex items-baseline gap-2 text-sm font-semibold text-foreground">
              {{ group.label }}
              <span class="text-xs font-normal tabular-nums text-muted-foreground">{{ resolved(group.steps) }}/{{ group.steps.length }}</span>
            </h3>
            <ul class="mt-2 divide-y divide-border border border-border">
              <LearningStepRow
                v-for="step in group.steps"
                :key="step.id"
                :step="step"
                :readonly="readonly"
                :coordinators="coordinators"
                :institution-contacts="institutionContacts"
              />
            </ul>
          </div>
        </section>
      </li>
    </ol>
    <EmptyState v-else :title="$t('learning.path.empty_title')" :description="$t('learning.path.empty_description')" />

    <slot name="before-meeting-prep" />

    <section v-if="path.meetingPrep.length" data-slot="meeting-prep" aria-labelledby="meeting-prep-title">
      <header class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <h2 id="meeting-prep-title" class="text-xl font-bold leading-tight sm:text-2xl">{{ $t('learning.path.meeting_prep') }}</h2>
        <span class="inline-flex items-center gap-1.5 border border-border px-2 py-0.5 text-xs font-semibold text-muted-foreground">
          <RefreshCw class="size-3.5" aria-hidden="true" />{{ $t('learning.path.meeting_prep_hint') }}
        </span>
      </header>
      <div v-for="group in path.meetingPrep" :key="group.audience.id" class="mt-4">
        <h3 class="text-sm font-semibold">{{ group.audience.label }}</h3>
        <ol class="mt-2 divide-y divide-border border border-border">
          <li v-for="(step, stepIndex) in group.steps" :key="step.id" class="grid grid-cols-[2.75rem_minmax(0,1fr)] items-start gap-x-3 px-4 py-4">
            <span class="mx-auto flex size-6 items-center justify-center border-2 border-border text-xs font-bold tabular-nums text-muted-foreground" aria-hidden="true">{{ stepIndex + 1 }}</span>
            <div class="min-w-0">
              <p class="text-lg font-bold leading-6">{{ step.title }}</p>
              <p v-if="step.description" class="mt-1 max-w-prose text-sm leading-relaxed text-muted-foreground">{{ step.description }}</p>
              <LearningAudienceNotes :notes="step.audienceNotes" class="mt-2" />
            </div>
          </li>
        </ol>
      </div>
    </section>
  </div>
</template>
