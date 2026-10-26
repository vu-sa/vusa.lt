<template>
  <label
    :for="controlId"
    :class="cn(
      'flex w-full cursor-pointer items-center justify-between gap-4 px-4 py-3.5 text-left transition-colors',
      'hover:bg-secondary/60 has-[:focus-visible]:outline-2 has-[:focus-visible]:-outline-offset-2 has-[:focus-visible]:outline-ring',
      props.class,
    )"
    data-slot="check-control"
  >
    <span class="text-sm font-medium text-foreground">
      <slot>{{ label }}</slot>
    </span>

    <Checkbox
      :id="controlId"
      v-bind="$attrs"
      :model-value
      :size="size === 'sm' ? 'md' : 'lg'"
      class="border-border bg-transparent"
      @update:model-value="modelValue = $event === true"
    >
      <IFluentCheckmark24Filled />
    </Checkbox>
  </label>
</template>

<script setup lang="ts">
import { computed, useId, type HTMLAttributes } from 'vue';

import IFluentCheckmark24Filled from '~icons/fluent/checkmark-24-filled';
import { Checkbox } from '@/Components/ui/checkbox';
import { cn } from '@/Utils/Shadcn/utils';

/** Use this square, full-row control instead of a pill switch on the public surface. */
defineOptions({ inheritAttrs: false });

const props = withDefaults(defineProps<{
  id?: string;
  label?: string;
  class?: HTMLAttributes['class'];
  size?: 'default' | 'sm';
}>(), {
  id: undefined,
  label: undefined,
  class: undefined,
  size: 'default',
});

const modelValue = defineModel<boolean>({ default: false });
const generatedId = useId();
const controlId = computed(() => props.id ?? generatedId);
</script>
