<script setup lang="ts">
import { computed, onBeforeUnmount, ref, useId, watch } from 'vue';
import { Zap } from '@lucide/vue';

import { Checkbox } from '@/Components/ui/checkbox';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import type { LearningStepData, KnowledgeItemData, SelfLearnedItemData } from '@/Types/learning';

type Trackable = LearningStepData | KnowledgeItemData | SelfLearnedItemData;
const props = defineProps<{ step: Trackable; disabled?: boolean }>();
const checked = defineModel<boolean | 'indeterminate'>({ required: true });

// A short pop and a drawn tick when you mark something done; reduced motion keeps only the state change.
const celebrating = ref(false);
let celebrationTimer: ReturnType<typeof setTimeout> | undefined;
watch(checked, (value, previous) => {
  if (value !== true || previous === true) return;
  celebrating.value = true;
  clearTimeout(celebrationTimer);
  celebrationTimer = setTimeout(() => { celebrating.value = false; }, 700);
});
onBeforeUnmount(() => clearTimeout(celebrationTimer));
const hintId = useId();
const automatic = computed(() => ('automaticallyTracked' in props.step && props.step.automaticallyTracked) || props.step.autoCompleted);
const branded = computed(() => props.step.importance === 'essential');
const appearance = computed(() => [
  'relative flex size-5 shrink-0 items-center justify-center border-2 bg-clip-padding',
  'pointer-coarse:before:absolute pointer-coarse:before:-inset-3 pointer-coarse:before:content-[\'\']',
  automatic.value && checked.value === true
    ? 'border-status-success-border'
    : branded.value ? 'border-brand data-[state=checked]:border-brand-fill' : 'border-muted-foreground data-[state=checked]:border-foreground',
  props.step.importance === 'optional' && 'border-dashed',
]);
</script>

<template>
  <div class="flex h-6 w-11 shrink-0 items-center justify-center" data-slot="learning-completion-control">
    <Tooltip v-if="automatic">
      <TooltipTrigger as-child>
        <span
          role="checkbox"
          tabindex="0"
          aria-readonly="true"
          aria-disabled="true"
          :aria-checked="checked === true"
          :aria-label="step.title"
          :aria-describedby="hintId"
          data-testid="step-checkbox"
          :class="[
            ...appearance,
            'cursor-default outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
            checked
              ? 'bg-status-success-surface text-status-success'
              : (branded ? 'bg-background text-brand' : 'bg-background text-muted-foreground'),
          ]"
        >
          <Zap class="size-3.5" role="img" :aria-label="$t(checked ? 'learning.path.auto_help' : 'learning.path.auto_pending')" />
        </span>
      </TooltipTrigger>
      <TooltipContent>{{ $t('learning.path.auto_tracking') }}</TooltipContent>
    </Tooltip>
    <span v-else class="relative flex">
      <Checkbox
        v-model="checked"
        :disabled="disabled"
        :aria-label="step.title"
        :aria-describedby="hintId"
        data-testid="step-checkbox"
        :data-celebrating="celebrating || undefined"
        :class="[
          ...appearance,
          'learning-check cursor-pointer disabled:cursor-default [&_svg]:size-3.5 [&_svg]:stroke-[3]',
          branded
            ? 'enabled:hover:border-brand enabled:hover:bg-brand/10 data-[state=checked]:bg-brand-fill data-[state=checked]:text-brand-foreground data-[state=checked]:enabled:hover:bg-brand-fill/90'
            : 'enabled:hover:border-foreground enabled:hover:bg-muted data-[state=checked]:bg-foreground data-[state=checked]:text-background data-[state=checked]:enabled:hover:bg-foreground/80',
        ]"
      />
      <span v-if="celebrating" :class="['learning-check-burst pointer-events-none absolute inset-0 border-2', branded ? 'border-brand' : 'border-foreground']" aria-hidden="true" />
    </span>
    <span :id="hintId" class="sr-only">
      {{ step.importance ? $t(`learning.importance.${step.importance}`) : $t('learning.path.self_learned') }}
      <template v-if="automatic">. {{ $t('learning.path.auto_tracking') }}</template>
    </span>
  </div>
</template>

<style scoped>
@keyframes learning-check-pop {
  0% { transform: scale(1); }
  40% { transform: scale(1.25); }
  100% { transform: scale(1); }
}

@keyframes learning-check-draw {
  from { stroke-dashoffset: 24; }
  to { stroke-dashoffset: 0; }
}

@keyframes learning-check-burst {
  from { opacity: 0.7; transform: scale(1); }
  to { opacity: 0; transform: scale(2.2); }
}

.learning-check[data-celebrating] {
  animation: learning-check-pop 320ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

.learning-check[data-celebrating] :deep(svg path) {
  stroke-dasharray: 24;
  animation: learning-check-draw 280ms ease-out 60ms both;
}

.learning-check-burst {
  animation: learning-check-burst 520ms ease-out forwards;
}

@media (prefers-reduced-motion: reduce) {
  .learning-check[data-celebrating],
  .learning-check[data-celebrating] :deep(svg path),
  .learning-check-burst {
    animation: none;
  }

  .learning-check-burst {
    display: none;
  }
}
</style>
