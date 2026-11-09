<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';

import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import { learningImportanceStatuses, learningProgressStatuses, statusRoleParts } from '@/Constants/statusIcons';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData } from '@/Types/learning';

import LearningStepContent from './LearningStepContent.vue';

defineProps<{
  step: LearningStepData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
}>();
</script>

<template>
  <details class="group border-b border-border" :data-step="step.id">
    <summary class="flex min-h-11 cursor-pointer list-none items-start gap-3 py-4 focus-visible:outline-2 focus-visible:outline-ring">
      <component :is="learningProgressStatuses[step.state].icon" :class="['mt-0.5 size-5 shrink-0', statusRoleParts[learningProgressStatuses[step.state].role].text]" aria-hidden="true" />
      <span class="flex min-w-0 flex-1 flex-col gap-2">
        <span class="text-sm font-semibold leading-relaxed">{{ step.title }}</span>
        <span class="flex flex-wrap items-center gap-2">
          <StatusBadge :status="learningImportanceStatuses[step.importance]" voice="sentence" />
          <span :class="['text-xs', step.state === 'pending' ? 'sr-only' : 'text-muted-foreground']">{{ $t(learningProgressStatuses[step.state].label) }}</span>
          <span v-if="step.autoCompleted" class="text-xs text-muted-foreground">{{ $t('learning.path.auto') }}</span>
        </span>
      </span>
      <ChevronDown class="mt-1 size-4 shrink-0 text-muted-foreground group-open:rotate-180" aria-hidden="true" />
    </summary>
    <div class="space-y-3 pb-5 sm:pl-8">
      <LearningStepContent :step="step" :readonly="readonly" :coordinators="coordinators" :institution-contacts="institutionContacts" />
      <Link :href="route('learningSteps.show', step.id) + '?tab=insights'" class="inline-flex min-h-11 items-center text-sm underline underline-offset-4">
        {{ $t('learning.path.insights_count', { count: step.insightsCount }) }}
      </Link>
    </div>
  </details>
</template>
