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
        size === 'sm' ? 'size-5' : 'size-6',
      ]"
      :style="categoryVariables"
      aria-hidden="true"
    >
      <component :is="definition.icon" :class="size === 'sm' ? 'size-3.5' : 'size-4'" />
    </span>
    <span>{{ $t(label ?? definition.label) }}</span>
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
  size?: 'sm' | 'md';
  class?: HTMLAttributes['class'];
}>(), {
  label: undefined,
  size: 'sm',
  class: undefined,
});

const definition = computed(() => getEntityTypeDefinition(props.type));
const categoryVariables = computed<CSSProperties>(() => ({
  '--entity-category': `var(--cat-${definition.value?.category ?? 1})`,
  '--entity-category-surface': `var(--cat-${definition.value?.category ?? 1}-surface)`,
}));
</script>
