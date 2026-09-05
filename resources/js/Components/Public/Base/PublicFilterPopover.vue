<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <button
        type="button"
        :class="cn(
          'inline-flex items-center justify-between gap-2 border px-3 py-2 text-xs font-bold uppercase tracking-wide transition-colors',
          selected.length > 0
            ? 'border-brand text-brand bg-brand/5 hover:bg-brand/10'
            : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
          props.triggerClass,
        )"
        :aria-label="label"
        data-slot="public-filter-popover-trigger"
      >
        <span class="truncate">{{ label }}</span>
        <span
          v-if="selected.length > 0"
          class="flex size-4 shrink-0 items-center justify-center bg-brand-fill text-brand-foreground text-[0.625rem] font-mono leading-none"
        >
          {{ selected.length }}
        </span>
        <IFluentChevronDown16Regular
          class="size-3.5 shrink-0 transition-transform duration-200"
          :class="{ 'rotate-180': isOpen }"
        />
      </button>
    </PopoverTrigger>

    <PopoverContent
      align="start"
      class="z-50 w-72 p-0 border border-border bg-popover text-popover-foreground shadow-lg sm:w-80"
      data-slot="public-filter-popover-content"
    >
      <!-- Popover Header -->
      <div class="flex items-center justify-between border-b border-border px-3.5 py-2.5">
        <span class="text-xs font-bold uppercase tracking-wider text-foreground">
          {{ label }}
          <span v-if="selected.length > 0" class="text-brand">({{ selected.length }})</span>
        </span>
        <button
          v-if="selected.length > 0"
          type="button"
          class="text-xs font-medium text-muted-foreground transition-colors hover:text-brand"
          @click="emit('clear')"
        >
          {{ $t('Išvalyti') }}
        </button>
      </div>

      <!-- Optional search for long lists (e.g. Padaliniai) -->
      <div v-if="searchable || options.length > 6" class="relative border-b border-border px-3 py-2">
        <IFluentSearch16Regular class="absolute left-5 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground" />
        <input
          v-model="searchTerm"
          type="text"
          :placeholder="searchPlaceholder || `${$t('Ieškoti')}...`"
          :class="[
            'w-full border border-border bg-secondary/50 py-1 pl-8 pr-7 text-xs text-foreground',
            'placeholder:text-muted-foreground transition-colors focus:border-brand focus:outline-none',
          ]"
        >
        <button
          v-if="searchTerm"
          type="button"
          class="absolute right-5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
          @click="searchTerm = ''"
        >
          <IFluentDismiss16Regular class="size-3" />
          <span class="sr-only">{{ $t('Išvalyti') }}</span>
        </button>
      </div>

      <!-- Options List -->
      <div class="max-h-60 overflow-y-auto divide-y divide-border/40">
        <template v-if="filteredOptions.length > 0">
          <button
            v-for="option in filteredOptions"
            :key="option.value"
            type="button"
            role="checkbox"
            :aria-checked="isSelected(option.value)"
            :class="[
              'flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left transition-colors hover:bg-secondary/60',
              'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
            ]"
            @click="emit('toggle', option.value)"
          >
            <span class="text-sm font-medium text-foreground truncate">
              {{ option.label }}
            </span>

            <div class="flex items-center gap-2 shrink-0">
              <span v-if="option.count !== undefined" class="text-xs font-mono text-muted-foreground">
                {{ option.count }}
              </span>

              <span
                :class="[
                  'flex size-5 shrink-0 items-center justify-center border-2 transition-colors',
                  isSelected(option.value)
                    ? 'border-brand-fill bg-brand-fill text-brand-foreground'
                    : 'border-border',
                ]"
                aria-hidden="true"
              >
                <IFluentCheckmark16Filled v-if="isSelected(option.value)" class="size-3.5" />
              </span>
            </div>
          </button>
        </template>
        <div v-else class="p-4 text-center text-xs text-muted-foreground italic">
          {{ $t('Parinkčių nerasta') }}
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ref, computed } from 'vue';

import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/Utils/Shadcn/utils';
import IFluentChevronDown16Regular from '~icons/fluent/chevron-down-16-regular';
import IFluentCheckmark16Filled from '~icons/fluent/checkmark-16-filled';
import IFluentSearch16Regular from '~icons/fluent/search-16-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';

export interface FilterOption {
  label: string;
  value: string;
  count?: number;
}

const props = withDefaults(defineProps<{
  label: string;
  options: FilterOption[];
  selected: string[];
  searchable?: boolean;
  searchPlaceholder?: string;
  triggerClass?: HTMLAttributes['class'];
}>(), {
  searchPlaceholder: '',
  triggerClass: undefined,
});

const emit = defineEmits<{
  (e: 'toggle', value: string): void;
  (e: 'clear'): void;
}>();

const isOpen = ref(false);
const searchTerm = ref('');

const isSelected = (value: string): boolean => props.selected.includes(value);

const filteredOptions = computed(() => {
  if (!searchTerm.value.trim()) return props.options;
  const term = searchTerm.value.toLowerCase().trim();
  return props.options.filter(opt => opt.label.toLowerCase().includes(term));
});
</script>
