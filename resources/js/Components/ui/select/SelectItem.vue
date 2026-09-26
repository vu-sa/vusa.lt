<template>
  <SelectItem
    data-slot="select-item"
    v-bind="forwardedProps"
    :class="cn(
      'focus:bg-accent focus:text-accent-foreground',
      '[&_svg:not([class*=\'text-\'])]:text-muted-foreground',
      'relative flex w-full cursor-default items-center gap-2',
      'py-1.5 pr-8 pl-2 text-sm outline-hidden select-none',
      'data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      '[&_svg]:pointer-events-none',
      '[&_svg]:shrink-0',
      '[&_svg:not([class*=\'size-\'])]:size-4',
      '*:[span]:last:flex',
      '*:[span]:last:items-center',
      '*:[span]:last:gap-2',
      props.class,
    )"
  >
    <span class="absolute right-2 flex size-3.5 items-center justify-center">
      <SelectItemIndicator>
        <Check class="size-4" />
      </SelectItemIndicator>
    </span>

    <template v-if="label">
      <SelectItemText class="sr-only">
        {{ label }}
      </SelectItemText>
      <component :is="icon" v-if="icon" aria-hidden="true" />
      <slot />
    </template>
    <SelectItemText v-else>
      <component :is="icon" v-if="icon" aria-hidden="true" />
      <slot />
    </SelectItemText>
  </SelectItem>
</template>

<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import {
  SelectItem,
  SelectItemIndicator,
  type SelectItemProps,
  SelectItemText,
  useForwardProps,
} from 'reka-ui';
import { computed, type Component, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<SelectItemProps & { class?: HTMLAttributes['class']; label?: string; icon?: Component }>();

const delegatedProps = computed(() => {
  const { class: _, icon: __, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>
