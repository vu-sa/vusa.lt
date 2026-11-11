<script setup lang="ts">
import { computed, useId } from 'vue';
import { Zap } from '@lucide/vue';

import { Checkbox } from '@/Components/ui/checkbox';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
import type { LearningStepData, KnowledgeItemData } from '@/Types/learning';

const props = defineProps<{ step: LearningStepData | KnowledgeItemData; disabled?: boolean }>();
const checked = defineModel<boolean | 'indeterminate'>({ required: true });
const hintId = useId();
const automatic = computed(() => ('automaticallyTracked' in props.step && props.step.automaticallyTracked) || props.step.autoCompleted);
const branded = computed(() => props.step.importance === 'essential');
const appearance = computed(() => [
  'relative flex size-5 shrink-0 items-center justify-center border bg-clip-padding',
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
    <Checkbox
      v-else
      v-model="checked"
      :disabled="disabled"
      :aria-label="step.title"
      :aria-describedby="hintId"
      data-testid="step-checkbox"
      :class="[
        ...appearance,
        'cursor-pointer disabled:cursor-default [&_svg]:size-3.5',
        branded
          ? 'enabled:hover:border-brand enabled:hover:bg-brand/10 data-[state=checked]:bg-brand-fill data-[state=checked]:text-brand-foreground data-[state=checked]:enabled:hover:bg-brand-fill/90'
          : 'enabled:hover:border-foreground enabled:hover:bg-muted data-[state=checked]:bg-foreground data-[state=checked]:text-background data-[state=checked]:enabled:hover:bg-foreground/80',
      ]"
    />
    <span :id="hintId" class="sr-only">
      {{ $t(`learning.importance.${step.importance}`) }}
      <template v-if="automatic">. {{ $t('learning.path.auto_tracking') }}</template>
    </span>
  </div>
</template>
