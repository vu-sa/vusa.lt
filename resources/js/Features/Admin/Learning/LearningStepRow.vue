<template>
  <li :data-step="step.id" :aria-busy="saving" :class="hasCoordinators ? 'pb-6' : 'pb-3'" class="grid grid-cols-[2.75rem_minmax(0,1fr)_auto] items-start gap-x-3 px-4 pt-3">
    <!-- The checkbox and title share the action buttons' height, so single-line rows centre on one axis. -->
    <div class="flex min-h-9 items-center pointer-coarse:min-h-11">
      <LearningCompletionControl v-model="checked" :step :disabled="saving || readonly" />
    </div>
    <div class="min-w-0">
      <div class="flex min-h-9 items-center gap-2 pointer-coarse:min-h-11" data-slot="step-title-row">
        <FileText v-if="isDocument" class="size-5 shrink-0 text-muted-foreground" role="img" :aria-label="$t('learning.path.kind_document')" />
        <Link :href="route('source' in step ? 'knowledgeItems.show' : 'learningSteps.show', step.id)" :class="['min-w-0 text-lg font-bold leading-6 underline decoration-border underline-offset-4 hover:decoration-foreground', isResolved ? 'text-muted-foreground' : 'text-foreground']">
          {{ step.title }}
        </Link>
      </div>
      <div v-if="isPrivateDocument || step.state === 'updated' || step.state === 'skipped'" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
        <span v-if="isPrivateDocument">{{ $t('learning.path.document_private') }}</span>
        <span v-if="step.state === 'updated'" class="font-semibold text-status-attention">{{ $t('learning.path.updated') }}</span>
        <span v-if="step.state === 'skipped'">{{ $t('learning.path.skipped') }}</span>
      </div>
      <LearningCoordinators v-if="hasCoordinators" :coordinators="coordinators ?? []" class="mt-3" />
      <p v-if="failed" role="alert" class="mt-2 text-sm text-destructive">
        {{ $t('learning.path.save_failed') }}
      </p>
    </div>
    <div class="col-start-2 row-start-2 flex min-w-0 flex-nowrap items-start gap-2 pt-2 sm:col-start-3 sm:row-start-1 sm:justify-end sm:pt-0">
      <LearningStepAction :step />
      <DropdownMenu v-if="canSkip">
        <DropdownMenuTrigger as-child>
          <button type="button" :class="[rowActionIconClass, rowActionHoverClass]" :disabled="saving" :aria-label="$t('learning.path.actions')">
            <MoreHorizontal class="size-4" aria-hidden="true" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem data-testid="skip-step" @select="save('skipped')">
            {{ $t('learning.path.skip') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <!-- Keeps the action buttons in one column whether or not the step can be skipped. -->
      <span v-else class="hidden size-9 shrink-0 sm:block pointer-coarse:size-11" aria-hidden="true" />
    </div>
  </li>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, toRef } from 'vue';
import { FileText, MoreHorizontal } from '@lucide/vue';

import { rowActionIconClass, rowActionHoverClass } from '@/Components/Collection/rowActionClasses';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData, KnowledgeItemData, SelfLearnedItemData } from '@/Types/learning';

import LearningCoordinators from './LearningCoordinators.vue';
import LearningCompletionControl from './LearningCompletionControl.vue';
import LearningStepAction from './LearningStepAction.vue';
import { useStepProgress } from './composables/useStepProgress';

const props = defineProps<{
  step: LearningStepData | KnowledgeItemData | SelfLearnedItemData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
}>();
const { saving, failed, canSkip, checked, save } = useStepProgress(toRef(props, 'step'), toRef(props, 'readonly'));
const isDocument = computed(() => 'source' in props.step);
const isPrivateDocument = computed(() => {
  const source = 'source' in props.step ? props.step.source : null;
  return source?.type === 'document' && (source.private === true || !source.url);
});
const isResolved = computed(() => props.step.state === 'done' || props.step.state === 'skipped');
const hasCoordinators = computed(() => 'key' in props.step && props.step.key === 'meet_coordinator' && Boolean(props.coordinators?.length));
</script>
