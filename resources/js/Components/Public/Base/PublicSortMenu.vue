<template>
  <DropdownMenu :modal="false">
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        data-slot="public-sort-menu"
        :class="['group', controlVariants({ active: isChanged })]"
        :aria-label="label"
      >
        <component :is="current?.icon" v-if="current?.icon" class="size-3.5" aria-hidden="true" />
        <span :class="labelClass">{{ label }}</span>
        <IFluentChevronDown16Regular class="size-3.5 transition-transform duration-200 group-data-[state=open]:rotate-180" aria-hidden="true" />
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="z-50 w-64 border border-border bg-popover p-0 text-popover-foreground shadow-lg">
      <DropdownMenuRadioGroup :model-value="model">
        <DropdownMenuLabel class="border-b border-border px-3.5 py-2.5 text-xs font-bold tracking-wider text-foreground uppercase">
          {{ heading ?? $t('Rikiuoti pagal') }}
        </DropdownMenuLabel>
        <DropdownMenuRadioItem
          v-for="option in options"
          :key="option.value"
          :value="option.value"
          :class="[
            'flex w-full items-center justify-between gap-3 border-b border-border/40 px-3.5 last:border-b-0 py-2.5 text-left text-sm font-medium pointer-coarse:min-h-11',
            'text-foreground transition-colors hover:bg-secondary/60 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
          ]"
          @select="model = option.value"
        >
          <span class="flex items-center gap-2">
            <component :is="option.icon" v-if="option.icon" class="size-4 text-muted-foreground" aria-hidden="true" />
            <span>{{ option.label }}</span>
          </span>
          <IFluentCheckmark16Filled v-if="model === option.value" class="size-4 text-brand" aria-hidden="true" />
        </DropdownMenuRadioItem>
      </DropdownMenuRadioGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts" generic="T extends string">
import { computed, type Component, type HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { DropdownMenuRadioItem } from 'reka-ui';

import { controlVariants } from '@/Components/ui/control';
import { DropdownMenu, DropdownMenuContent, DropdownMenuLabel, DropdownMenuRadioGroup, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentCheckmark16Filled from '~icons/fluent/checkmark-16-filled';

export interface PublicSortOption<V extends string = string> {
  value: V;
  label: string;
  icon?: Component;
}

/** The "Rikiuoti" control beside a public search: a menu of radio rows; tinted once moved off the default. */
const props = defineProps<{
  options: PublicSortOption<T>[];
  /** The order the listing starts in; any other choice tints the trigger. */
  defaultValue: T;
  label?: string;
  heading?: string;
  /** Hides the visible label in a crowded toolbar (e.g. `hidden lg:inline`); the trigger keeps its aria-label. */
  labelClass?: HTMLAttributes['class'];
}>();

const model = defineModel<T>({ required: true });

const label = computed(() => props.label ?? $t('Rikiuoti'));
const current = computed(() => props.options.find(option => option.value === model.value));
const isChanged = computed(() => model.value !== props.defaultValue);
</script>
