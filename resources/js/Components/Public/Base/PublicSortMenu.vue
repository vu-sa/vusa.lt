<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <button
        type="button"
        data-slot="public-sort-menu"
        :class="[
          'inline-flex h-11 shrink-0 items-center justify-between gap-2 border px-3.5 text-xs font-bold tracking-wide uppercase transition-colors',
          isChanged
            ? 'border-brand bg-brand/5 text-brand hover:bg-brand/10'
            : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
        ]"
        :aria-label="label"
      >
        <component :is="current?.icon" v-if="current?.icon" class="size-3.5" aria-hidden="true" />
        <span>{{ label }}</span>
        <IFluentChevronDown16Regular class="size-3.5 transition-transform duration-200" :class="{ 'rotate-180': open }" aria-hidden="true" />
      </button>
    </PopoverTrigger>

    <PopoverContent align="end" class="z-50 w-64 border border-border bg-popover p-0 text-popover-foreground shadow-lg">
      <div class="border-b border-border px-3.5 py-2.5 text-xs font-bold tracking-wider text-foreground uppercase">
        {{ heading ?? $t('Rikiuoti pagal') }}
      </div>
      <div role="radiogroup" :aria-label="heading ?? $t('Rikiuoti pagal')" class="divide-y divide-border/40">
        <button
          v-for="option in options"
          :key="option.value"
          type="button"
          role="radio"
          :aria-checked="model === option.value"
          :class="[
            'flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left text-sm font-medium pointer-coarse:min-h-11',
            'text-foreground transition-colors hover:bg-secondary/60 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
          ]"
          @click="select(option.value)"
        >
          <span class="flex items-center gap-2">
            <component :is="option.icon" v-if="option.icon" class="size-4 text-muted-foreground" aria-hidden="true" />
            <span>{{ option.label }}</span>
          </span>
          <IFluentCheckmark16Filled v-if="model === option.value" class="size-4 text-brand" aria-hidden="true" />
        </button>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts" generic="T extends string">
import { computed, ref, type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentCheckmark16Filled from '~icons/fluent/checkmark-16-filled';

export interface PublicSortOption<V extends string = string> {
  value: V;
  label: string;
  icon?: Component;
}

/** The "Rikiuoti" control beside a public search: a popover of radio rows; tinted once moved off the default. */
const props = defineProps<{
  options: PublicSortOption<T>[];
  /** The order the listing starts in; any other choice tints the trigger. */
  defaultValue: T;
  label?: string;
  heading?: string;
}>();

const model = defineModel<T>({ required: true });

const open = ref(false);
const label = computed(() => props.label ?? $t('Rikiuoti'));
const current = computed(() => props.options.find(option => option.value === model.value));
const isChanged = computed(() => model.value !== props.defaultValue);

function select(value: T): void {
  model.value = value;
  open.value = false;
}
</script>
