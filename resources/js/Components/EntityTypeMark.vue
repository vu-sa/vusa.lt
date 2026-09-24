<template>
  <span
    v-if="definition"
    :class="cn('inline-flex w-fit items-center gap-2 text-sm font-medium', props.class)"
    data-slot="entity-type-mark"
    :data-entity-type="definition.type"
    :data-entity-category="definition.category"
  >
    <span
      :class="[
        'inline-flex shrink-0 items-center justify-center bg-[var(--entity-category-surface)] text-[var(--entity-category)]',
        boxClass,
      ]"
      :style="categoryVariables"
      aria-hidden="true"
    >
      <component :is="definition.icon" :class="iconClass" />
    </span>
    <span v-if="!iconOnly">{{ $t(label ?? definition.label) }}</span>
  </span>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import type { CSSProperties, HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import type { ModelEnum } from '@/Types/enums';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  type: ModelEnum | keyof typeof ModelEnum | string;
  label?: string;
  /** `lg` and `xl` are picture placeholders (a row thumbnail, a card image). */
  size?: 'sm' | 'md' | 'lg' | 'xl';
  /** A stand-in for a missing picture next to the record's own name: the label would only repeat the type. */
  iconOnly?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  label: undefined,
  size: 'sm',
  class: undefined,
});

const definition = computed(() => getEntityTypeDefinition(props.type));

const SIZES = {
  sm: { box: 'size-5', icon: 'size-3.5' },
  md: { box: 'size-6', icon: 'size-4' },
  lg: { box: 'size-10', icon: 'size-5' },
  xl: { box: 'size-16', icon: 'size-8' },
} as const;

const boxClass = computed(() => SIZES[props.size].box);
const iconClass = computed(() => SIZES[props.size].icon);
const categoryVariables = computed<CSSProperties>(() => ({
  '--entity-category': `var(--cat-${definition.value?.category ?? 1})`,
  '--entity-category-surface': `var(--cat-${definition.value?.category ?? 1}-surface)`,
}));
</script>
