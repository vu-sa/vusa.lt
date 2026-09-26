<template>
  <component
    :is="as"
    :class="cn(
      'flex flex-wrap',
      align === 'center' ? 'justify-center' : 'justify-start',
      topRule === 'full' && 'border-t border-border',
      '[&>*]:min-w-0 [&>*]:shrink-0 [&>*]:grow-0 [&>*]:border-b [&>*]:border-l [&>*]:border-border',
      basisClasses.base[resolvedColumns.base],
      basisClasses.sm[resolvedColumns.sm],
      basisClasses.lg[resolvedColumns.lg],
      basisClasses.xl[resolvedColumns.xl],
      rightEdgeClasses.base[resolvedColumns.base],
      rightEdgeClasses.sm[resolvedColumns.sm],
      rightEdgeClasses.lg[resolvedColumns.lg],
      rightEdgeClasses.xl[resolvedColumns.xl],
      topRule === 'cells' && firstRowClasses.base[resolvedColumns.base],
      topRule === 'cells' && firstRowClasses.sm[resolvedColumns.sm],
      topRule === 'cells' && firstRowClasses.lg[resolvedColumns.lg],
      topRule === 'cells' && firstRowClasses.xl[resolvedColumns.xl],
      props.class,
    )"
    data-slot="ruled-grid"
  >
    <slot />
  </component>
</template>

<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

type ColumnCount = 1 | 2 | 3 | 4 | 5 | 6;
type Breakpoint = 'base' | 'sm' | 'lg' | 'xl';

export interface RuledGridColumns {
  base: ColumnCount;
  sm?: ColumnCount;
  lg?: ColumnCount;
  xl?: ColumnCount;
}

const props = withDefaults(defineProps<{
  as?: 'div' | 'ul' | 'dl';
  columns: RuledGridColumns;
  align?: 'start' | 'center';
  topRule?: 'none' | 'full' | 'cells';
  class?: HTMLAttributes['class'];
}>(), {
  as: 'div',
  align: 'start',
  topRule: 'none',
  class: undefined,
});

const resolvedColumns = computed(() => ({
  base: props.columns.base,
  sm: props.columns.sm ?? props.columns.base,
  lg: props.columns.lg ?? props.columns.sm ?? props.columns.base,
  xl: props.columns.xl ?? props.columns.lg ?? props.columns.sm ?? props.columns.base,
}));

const basisClasses: Record<Breakpoint, Record<ColumnCount, string>> = {
  base: {
    1: '[&>*]:basis-full', 2: '[&>*]:basis-1/2', 3: '[&>*]:basis-1/3',
    4: '[&>*]:basis-1/4', 5: '[&>*]:basis-1/5', 6: '[&>*]:basis-1/6',
  },
  sm: {
    1: 'sm:[&>*]:basis-full', 2: 'sm:[&>*]:basis-1/2', 3: 'sm:[&>*]:basis-1/3',
    4: 'sm:[&>*]:basis-1/4', 5: 'sm:[&>*]:basis-1/5', 6: 'sm:[&>*]:basis-1/6',
  },
  lg: {
    1: 'lg:[&>*]:basis-full', 2: 'lg:[&>*]:basis-1/2', 3: 'lg:[&>*]:basis-1/3',
    4: 'lg:[&>*]:basis-1/4', 5: 'lg:[&>*]:basis-1/5', 6: 'lg:[&>*]:basis-1/6',
  },
  xl: {
    1: 'xl:[&>*]:basis-full', 2: 'xl:[&>*]:basis-1/2', 3: 'xl:[&>*]:basis-1/3',
    4: 'xl:[&>*]:basis-1/4', 5: 'xl:[&>*]:basis-1/5', 6: 'xl:[&>*]:basis-1/6',
  },
};

// Exclusive ranges avoid a narrower tier's nth-child rule winning at wider widths.
const rightEdgeClasses: Record<Breakpoint, Record<ColumnCount, string>> = {
  base: {
    1: 'max-sm:[&>*:is(:nth-child(1n),:last-child)]:border-r',
    2: 'max-sm:[&>*:is(:nth-child(2n),:last-child)]:border-r',
    3: 'max-sm:[&>*:is(:nth-child(3n),:last-child)]:border-r',
    4: 'max-sm:[&>*:is(:nth-child(4n),:last-child)]:border-r',
    5: 'max-sm:[&>*:is(:nth-child(5n),:last-child)]:border-r',
    6: 'max-sm:[&>*:is(:nth-child(6n),:last-child)]:border-r',
  },
  sm: {
    1: 'sm:max-lg:[&>*:is(:nth-child(1n),:last-child)]:border-r',
    2: 'sm:max-lg:[&>*:is(:nth-child(2n),:last-child)]:border-r',
    3: 'sm:max-lg:[&>*:is(:nth-child(3n),:last-child)]:border-r',
    4: 'sm:max-lg:[&>*:is(:nth-child(4n),:last-child)]:border-r',
    5: 'sm:max-lg:[&>*:is(:nth-child(5n),:last-child)]:border-r',
    6: 'sm:max-lg:[&>*:is(:nth-child(6n),:last-child)]:border-r',
  },
  lg: {
    1: 'lg:max-xl:[&>*:is(:nth-child(1n),:last-child)]:border-r',
    2: 'lg:max-xl:[&>*:is(:nth-child(2n),:last-child)]:border-r',
    3: 'lg:max-xl:[&>*:is(:nth-child(3n),:last-child)]:border-r',
    4: 'lg:max-xl:[&>*:is(:nth-child(4n),:last-child)]:border-r',
    5: 'lg:max-xl:[&>*:is(:nth-child(5n),:last-child)]:border-r',
    6: 'lg:max-xl:[&>*:is(:nth-child(6n),:last-child)]:border-r',
  },
  xl: {
    1: 'xl:[&>*:is(:nth-child(1n),:last-child)]:border-r',
    2: 'xl:[&>*:is(:nth-child(2n),:last-child)]:border-r',
    3: 'xl:[&>*:is(:nth-child(3n),:last-child)]:border-r',
    4: 'xl:[&>*:is(:nth-child(4n),:last-child)]:border-r',
    5: 'xl:[&>*:is(:nth-child(5n),:last-child)]:border-r',
    6: 'xl:[&>*:is(:nth-child(6n),:last-child)]:border-r',
  },
};

const firstRowClasses: Record<Breakpoint, Record<ColumnCount, string>> = {
  base: {
    1: 'max-sm:[&>*:nth-child(-n+1)]:border-t',
    2: 'max-sm:[&>*:nth-child(-n+2)]:border-t',
    3: 'max-sm:[&>*:nth-child(-n+3)]:border-t',
    4: 'max-sm:[&>*:nth-child(-n+4)]:border-t',
    5: 'max-sm:[&>*:nth-child(-n+5)]:border-t',
    6: 'max-sm:[&>*:nth-child(-n+6)]:border-t',
  },
  sm: {
    1: 'sm:max-lg:[&>*:nth-child(-n+1)]:border-t',
    2: 'sm:max-lg:[&>*:nth-child(-n+2)]:border-t',
    3: 'sm:max-lg:[&>*:nth-child(-n+3)]:border-t',
    4: 'sm:max-lg:[&>*:nth-child(-n+4)]:border-t',
    5: 'sm:max-lg:[&>*:nth-child(-n+5)]:border-t',
    6: 'sm:max-lg:[&>*:nth-child(-n+6)]:border-t',
  },
  lg: {
    1: 'lg:max-xl:[&>*:nth-child(-n+1)]:border-t',
    2: 'lg:max-xl:[&>*:nth-child(-n+2)]:border-t',
    3: 'lg:max-xl:[&>*:nth-child(-n+3)]:border-t',
    4: 'lg:max-xl:[&>*:nth-child(-n+4)]:border-t',
    5: 'lg:max-xl:[&>*:nth-child(-n+5)]:border-t',
    6: 'lg:max-xl:[&>*:nth-child(-n+6)]:border-t',
  },
  xl: {
    1: 'xl:[&>*:nth-child(-n+1)]:border-t',
    2: 'xl:[&>*:nth-child(-n+2)]:border-t',
    3: 'xl:[&>*:nth-child(-n+3)]:border-t',
    4: 'xl:[&>*:nth-child(-n+4)]:border-t',
    5: 'xl:[&>*:nth-child(-n+5)]:border-t',
    6: 'xl:[&>*:nth-child(-n+6)]:border-t',
  },
};
</script>
