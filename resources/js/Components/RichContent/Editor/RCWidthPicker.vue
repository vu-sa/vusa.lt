<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        class="flex h-6 items-center gap-1 rounded px-1.5 text-zinc-500 transition-colors hover:bg-zinc-200 dark:hover:bg-zinc-700"
        :title="$t('rich-content.width')"
      >
        <component :is="WIDTH_ICON[modelValue]" class="h-3.5 w-3.5" />
        <IFluentChevronDown12Regular class="h-2.5 w-2.5 opacity-60" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-44">
      <DropdownMenuItem v-for="width in allowedWidths" :key="width" @click="$emit('update:modelValue', width)">
        <component :is="WIDTH_ICON[width]" class="mr-2 h-4 w-4" />
        {{ $t(`rich-content.width_${width}`) }}
        <IFluentCheckmark12Regular v-if="width === modelValue" class="ml-auto h-3 w-3" />
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { BlockWidth } from '../Types';

import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import IFluentChevronDown12Regular from '~icons/fluent/chevron-down12-regular';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';
import IFluentTextColumnTwo24Regular from '~icons/fluent/text-column-two24-regular';
import IFluentPanelLeftText24Regular from '~icons/fluent/panel-left-text24-regular';
import IFluentColumnTriple24Regular from '~icons/fluent/column-triple24-regular';
import IFluentArrowAutofitWidth24Regular from '~icons/fluent/arrow-autofit-width24-regular';

const WIDTH_ICON: Record<BlockWidth, unknown> = {
  prose: IFluentTextColumnTwo24Regular,
  content: IFluentPanelLeftText24Regular,
  wide: IFluentColumnTriple24Regular,
  full: IFluentArrowAutofitWidth24Regular,
};

defineProps<{
  modelValue: BlockWidth;
  allowedWidths: BlockWidth[];
}>();

defineEmits<{
  (e: 'update:modelValue', value: BlockWidth): void;
}>();
</script>
