<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        :class="controlVariants({ voice: 'sentence', active: modelValue !== 'all' })"
        data-slot="type-filter-trigger"
      >
        <component :is="activeOption.icon" class="size-4" aria-hidden="true" />
        <span>{{ activeOption.label }}</span>
        <ChevronDown class="size-3.5 opacity-60" aria-hidden="true" />
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="start" class="w-72 rounded-none p-1">
      <DropdownMenuItem
        v-for="option in options"
        :key="option.id"
        :class="[
          'min-h-11 cursor-pointer items-start gap-3 rounded-none py-2',
          option.count === 0 && option.id !== 'all' && 'opacity-60',
        ]"
        :data-type-filter="option.id"
        @select="$emit('update:modelValue', option.id)"
      >
        <component :is="option.icon" class="mt-0.5 size-4" aria-hidden="true" />
        <span class="min-w-0 flex-1">
          <span class="flex items-center justify-between gap-2">
            <span :class="['text-sm', modelValue === option.id && 'font-semibold text-brand']">{{ option.label }}</span>
            <span class="font-mono text-xs text-muted-foreground tabular-nums">{{ option.count }}</span>
          </span>
          <span v-if="option.extensions" class="mt-0.5 block truncate text-xs text-muted-foreground" :title="option.extensionsTitle">
            {{ option.extensions }}
          </span>
        </span>
        <Check v-if="modelValue === option.id" class="mt-0.5 size-4 text-brand" aria-hidden="true" />
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Check, ChevronDown, Files, Folder } from '@lucide/vue';

import type { TypeFilter } from '../types';
import { extensionsForKind, TYPE_FILTER_KINDS } from '../utils';

import { controlVariants } from '@/Components/ui/control';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { getFileKindIcon } from '@/Utils/fileIcons';

interface TypeOption {
  id: TypeFilter;
  label: string;
  icon: Component;
  count: number;
  extensions?: string;
  extensionsTitle?: string;
}

const props = defineProps<{
  modelValue: TypeFilter;
  /** Files of each kind in the current listing. */
  counts: Partial<Record<TypeFilter, number>>;
  folderCount: number;
  totalCount: number;
}>();

defineEmits<{
  'update:modelValue': [value: TypeFilter];
}>();

const SHOWN_EXTENSIONS = 6;

const options = computed<TypeOption[]>(() => [
  { id: 'all', label: $t('files.kinds.all'), icon: Files, count: props.totalCount },
  { id: 'folder', label: $t('files.kinds.folder'), icon: Folder, count: props.folderCount },
  ...TYPE_FILTER_KINDS.map((kind) => {
    const extensions = extensionsForKind(kind).map(ext => ext.toUpperCase());
    return {
      id: kind,
      label: $t(`files.kinds.${kind}`),
      icon: getFileKindIcon(kind),
      count: props.counts[kind] ?? 0,
      extensions: kind === 'other'
        ? $t('files.kinds.other_hint')
        : extensions.slice(0, SHOWN_EXTENSIONS).join(', ') + (extensions.length > SHOWN_EXTENSIONS ? '…' : ''),
      extensionsTitle: extensions.join(', '),
    };
  }),
]);

const activeOption = computed(() => options.value.find(option => option.id === props.modelValue) ?? options.value[0]);
</script>
