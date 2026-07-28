<template>
  <div class="group rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/50 overflow-hidden">
    <!-- Header — the only chrome. Drag handle, type, derived summary, width, preview, menu. -->
    <div class="flex items-center gap-1 px-2 py-1.5 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30">
      <div
        class="rc-drag-handle flex h-6 w-5 shrink-0 cursor-grab items-center justify-center rounded text-zinc-300 transition-colors hover:bg-zinc-200 hover:text-zinc-500 active:cursor-grabbing dark:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-400"
        :title="$t('rich-content.drag_to_reorder')"
      >
        <IFluentReOrderDotsVertical24Regular class="h-3.5 w-3.5" />
      </div>

      <button
        type="button"
        class="flex min-w-0 flex-1 items-center gap-1.5 rounded py-0.5 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800"
        :title="collapsed ? $t('rich-content.expand_block') : $t('rich-content.collapse_block')"
        @click="$emit('update:collapsed', !collapsed)"
      >
        <IFluentChevronRight12Regular
          class="h-3 w-3 shrink-0 text-zinc-400 transition-transform"
          :class="{ 'rotate-90': !collapsed }"
        />
        <component :is="contentType.icon" class="h-3.5 w-3.5 shrink-0 text-zinc-500" />
        <span class="shrink-0 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ contentType.label }}</span>
        <span class="truncate text-xs text-zinc-400 dark:text-zinc-500">{{ summary }}</span>
      </button>

      <span v-if="content?.id" class="shrink-0 text-[10px] text-zinc-400">#{{ content.id }}</span>
      <span v-else class="shrink-0 text-[10px] font-medium text-emerald-600 dark:text-emerald-400">{{ $t('New') }}</span>

      <RCWidthPicker
        v-if="allowedWidths.length > 1"
        :model-value="currentWidth"
        :allowed-widths="allowedWidths"
        @update:model-value="setWidth"
      />

      <button
        type="button"
        class="flex h-6 w-6 shrink-0 items-center justify-center rounded transition-colors"
        :class="previewMode
          ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300'
          : 'text-zinc-500 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
        :title="previewMode ? $t('rich-content.switch_to_edit') : $t('rich-content.switch_to_preview')"
        @click="$emit('update:previewMode', !previewMode)"
      >
        <IFluentEdit24Regular v-if="previewMode" class="h-3.5 w-3.5" />
        <IFluentEye24Regular v-else class="h-3.5 w-3.5" />
      </button>

      <button
        type="button"
        class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-zinc-500 transition-colors hover:bg-zinc-200 dark:hover:bg-zinc-700"
        :title="$t('rich-content.side_by_side')"
        @click="showSideBySide = true"
      >
        <IFluentLayoutColumnTwoSplitLeft24Regular class="h-3.5 w-3.5" />
      </button>

      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <button
            type="button"
            class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-zinc-500 transition-colors hover:bg-zinc-200 dark:hover:bg-zinc-700"
            :title="$t('rich-content.block_options')"
          >
            <IFluentMoreVertical24Regular class="h-3.5 w-3.5" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem :disabled="!canMoveUp" @click="$emit('move-up')">
            <IFluentArrowUp24Regular class="mr-2 h-4 w-4" />
            {{ $t('rich-content.move_up') }}
          </DropdownMenuItem>
          <DropdownMenuItem :disabled="!canMoveDown" @click="$emit('move-down')">
            <IFluentArrowDown24Regular class="mr-2 h-4 w-4" />
            {{ $t('rich-content.move_down') }}
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem :disabled="!canDelete" class="text-red-600 dark:text-red-400" @click="$emit('delete')">
            <IFluentDismiss24Regular class="mr-2 h-4 w-4" />
            {{ $t('rich-content.delete_block') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <!-- Collapsible body. v-show (not v-if) keeps the editor mounted while collapsed, so
         TipTap instances / form state survive a collapse-expand round trip. -->
    <div v-show="!collapsed" data-rc-block-body>
      <div class="p-3">
        <ContentEditorFactory
          :content
          :preview-mode="previewMode"
          :tenant-id="tenantId"
          @update:content="$emit('update:content', $event)"
        />
      </div>
      <div
        v-if="content?.type === 'text-box' && content?.id"
        class="border-t border-zinc-100 dark:border-zinc-800 px-3 py-2"
      >
        <TextBoxSubmissionsDialog :content-part-id="content.id" />
      </div>
    </div>

    <!-- Always mounted (so its own open/close transition can play), but its editor
         instance (e.g. a second TipTap) only mounts internally while `open` — see the
         `v-if="open"` inside RCSideBySideDialog — so it never runs alongside the
         inline block's own editor above. -->
    <RCSideBySideDialog
      :open="showSideBySide"
      :content="content"
      :tenant-id="tenantId"
      @update:open="showSideBySide = $event"
      @update:content="$emit('update:content', $event)"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { deriveBlockSummary } from './blockSummary';
import RCSideBySideDialog from './RCSideBySideDialog.vue';
import RCWidthPicker from './RCWidthPicker.vue';

import ContentEditorFactory from '../ContentEditorFactory.vue';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import TextBoxSubmissionsDialog from '../Types/TextBoxSubmissionsDialog.vue';

import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import IFluentReOrderDotsVertical24Regular from '~icons/fluent/re-order-dots-vertical24-regular';
import IFluentChevronRight12Regular from '~icons/fluent/chevron-right12-regular';
import IFluentEye24Regular from '~icons/fluent/eye24-regular';
import IFluentLayoutColumnTwoSplitLeft24Regular from '~icons/fluent/layout-column-two-split-left24-regular';
import IFluentEdit24Regular from '~icons/fluent/edit24-regular';
import IFluentMoreVertical24Regular from '~icons/fluent/more-vertical24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentDismiss24Regular from '~icons/fluent/dismiss24-regular';

const props = defineProps<{
  content: ContentPart;
  collapsed: boolean;
  previewMode: boolean;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
  /** Tenant the page/news article being edited belongs to — for server-resolved previews. */
  tenantId?: number | null;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'update:collapsed', value: boolean): void;
  (e: 'update:previewMode', value: boolean): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
}>();

const contentType = computed(() => getContentType(props.content?.type));
const summary = computed(() => deriveBlockSummary(props.content));

const showSideBySide = ref(false);

const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (props.content?.options?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function setWidth(width: BlockWidth) {
  emit('update:content', {
    ...props.content,
    options: { ...(props.content.options ?? {}), width },
  });
}
</script>
