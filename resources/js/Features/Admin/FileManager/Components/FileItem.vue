<template>
  <div
    :class="[
      'group relative flex flex-col border bg-background transition-colors',
      checked || selected
        ? 'border-brand ring-1 ring-brand'
        : 'border-border hover:border-muted-foreground/40',
    ]"
    data-slot="file-item"
  >
    <button
      type="button"
      class="flex min-w-0 flex-col text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40"
      :aria-pressed="selectionMode ? undefined : (checked || selected)"
      :aria-label="item.name"
      @click="$emit('click', $event)"
      @dblclick="$emit('dblclick')"
    >
      <FileThumb
        :path="item.path"
        :name="item.name"
        :width="320"
        icon-class="size-10 text-muted-foreground"
        class="aspect-[4/3] w-full border-b border-border"
      />
      <span class="flex min-w-0 flex-col gap-0.5 py-2 pr-10 pl-2.5">
        <span class="truncate text-sm font-medium text-foreground" :title="item.name">{{ item.name }}</span>
        <span class="flex items-center gap-1 truncate text-xs text-muted-foreground">
          <Star v-if="starred" class="size-3 shrink-0 fill-status-attention text-status-attention" :aria-label="$t('files.ui.starred')" />
          <span class="uppercase">{{ extension }}</span>
          <span aria-hidden="true">·</span>
          <span>{{ formatBytes(item.size) }}</span>
        </span>
        <span v-if="item.directory" class="truncate text-xs text-muted-foreground" :title="item.directory">{{ relativeToRoot(item.directory) || $t('files.ui.root') }}</span>
      </span>
    </button>

    <!-- The checkbox inside is the control; this wrapper widens its hit area and reads the shift key. -->
    <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
    <span
      v-if="!selectionMode"
      :class="[
        'absolute top-1 left-1 flex size-8 items-center justify-center transition-opacity pointer-coarse:size-11',
        checked || showCheckbox
          ? 'opacity-100'
          : 'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 pointer-coarse:opacity-100',
      ]"
      @click.stop="$emit('toggleCheck', $event)"
    >
      <Checkbox
        :model-value="checked"
        class="bg-background/95"
        :aria-label="checked ? $t('files.ui.uncheck', { name: item.name }) : $t('files.ui.check', { name: item.name })"
      />
    </span>

    <Button
      v-if="canPreview"
      variant="outline"
      size="icon-sm"
      :class="[
        'absolute top-1.5 right-1.5 bg-background/95',
        'opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100 pointer-coarse:opacity-100',
      ]"
      :title="$t('files.ui.preview')"
      :aria-label="$t('files.ui.preview_name', { name: item.name })"
      @click.stop="$emit('preview')"
    >
      <Eye class="size-4" aria-hidden="true" />
    </Button>

    <ItemActionsMenu
      v-if="actions && actions.length > 0"
      :label="item.name"
      :actions
      trigger-class="absolute right-1 bottom-1.5"
      @select="$emit('action', $event)"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, Star } from '@lucide/vue';

import type { FileEntry, ItemAction } from '../types';
import { formatBytes, relativeToRoot } from '../utils';

import FileThumb from './FileThumb.vue';
import ItemActionsMenu from './ItemActionsMenu.vue';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { getFileExtension } from '@/Utils/fileIcons';

const props = withDefaults(defineProps<{
  item: FileEntry;
  /** Open in the details sheet. */
  selected?: boolean;
  /** Ticked for a bulk action. */
  checked?: boolean;
  starred?: boolean;
  /** Something is ticked, so every checkbox stays visible. */
  showCheckbox?: boolean;
  selectionMode?: boolean;
  canPreview?: boolean;
  actions?: ItemAction[];
}>(), {
  actions: undefined,
});

defineEmits<{
  click: [event: MouseEvent];
  dblclick: [];
  toggleCheck: [event: MouseEvent];
  preview: [];
  action: [key: string];
}>();

const extension = computed(() => getFileExtension(props.item.name) || '—');
</script>
