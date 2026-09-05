<template>
  <div class="mt-4 w-full">
    <!-- Initial loading state -->
    <div v-if="isInitialLoading" class="space-y-6">
      <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
        <div class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent dark:border-zinc-600" />
        Loading content editor...
      </div>
      <div v-for="i in 2" :key="i" class="space-y-4">
        <div class="flex items-center gap-2">
          <Skeleton class="h-4 w-4 rounded" />
          <Skeleton class="h-4 w-32" />
        </div>
        <Skeleton class="h-32 w-full rounded-lg" />
      </div>
    </div>

    <!-- One calm container for the whole editor — blocks are plain cards inside it,
         no separators, no floating handles outside the content column. -->
    <div v-else class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-700 dark:bg-zinc-900/30">
      <div class="mb-3 flex items-center justify-between gap-2">
        <FadeTransition v-if="showHistory">
          <div class="flex items-center gap-2">
            <ButtonGroup>
              <Button size="icon-xs" variant="outline" :disabled="history?.length < 2" @click="undo()">
                <IFluentArrowUndo24Filled />
              </Button>
              <Button size="icon-xs" variant="outline" @click="redo()">
                <IFluentArrowRedo24Filled />
              </Button>
            </ButtonGroup>
            <p class="text-xs leading-5 text-zinc-400">
              {{ $t('rich-content.restore_content_block_order') }}
            </p>
          </div>
        </FadeTransition>

        <div class="ml-auto flex items-center gap-3">
          <ButtonGroup v-if="(contents?.length ?? 0) > 1">
            <Button size="xs" variant="outline" @click="collapseAll">
              {{ $t('rich-content.collapse_all') }}
            </Button>
            <Button size="xs" variant="outline" @click="expandAll">
              {{ $t('rich-content.expand_all') }}
            </Button>
          </ButtonGroup>
          <Button size="xs" variant="outline" @click="isFullscreenOpen = true">
            <IFluentFullScreenMaximize24Regular class="mr-1.5 h-3.5 w-3.5" />
            {{ $t('rich-content.fullscreen_editor') }}
          </Button>
        </div>
      </div>

      <!-- Full-screen editor. `v-if`, never `v-show`: forms mode's per-block editors
           (TipTap instances included) must be fully unmounted while the full-screen
           editor is open, and vice versa — two live editors bound to the same content is
           the one invariant this whole feature can't violate. Since `contents` is the
           same `defineModel` array throughout, nothing is lost tearing forms-mode editors
           down and back up. -->
      <RCFullscreenEditor
        v-if="isFullscreenOpen"
        v-model:contents="contents"
        :tenant-id="tenantId"
        :history="{ commit, undo, redo, canUndo, canRedo }"
        @close="isFullscreenOpen = false"
        @save="$emit('save')"
      />

      <!-- Forms mode - sortable blocks -->
      <template v-if="!isFullscreenOpen">
        <!-- Sortable container - ref must be on the direct parent of sortable items -->
        <TransitionGroup ref="sortableEl" tag="div" class="space-y-2" :class="{ 'rc-dragging': isDragging }">
          <div v-for="content, index in contents" :key="content?.id ?? content?.key" class="relative">
            <!-- Insert-between affordance: a thin hover-revealed line, not a floating circle -->
            <RCInsertAffordance
              v-if="index > 0"
              compact
              :quick-add-types="quickAddTypes"
              @insert="insertContentAt($event, index)"
              @more="showInsertMenuAt = index; showSelection = true"
            />

            <RCBlockCard
              :content
              :collapsed="isBlockCollapsed(content)"
              :preview-mode="isBlockInPreviewMode(content)"
              :presentation-disabled="bandMap.get(content)?.isSectionChild"
              :can-move-up="index > 0"
              :can-move-down="(contents?.length ?? 0) > index + 1"
              :can-delete="(contents?.length ?? 0) > 1"
              :tenant-id="tenantId"
              @update:content="(val) => contents![index] = val"
              @update:collapsed="setBlockCollapsed(content, $event)"
              @update:preview-mode="setBlockPreviewMode(content, $event)"
              @move-up="contents?.length && moveArrayElement(contents, index, index - 1)"
              @move-down="contents?.length && moveArrayElement(contents, index, index + 1)"
              @delete="handleElementRemove(index)"
            />
          </div>
        </TransitionGroup>
      </template>

      <!-- Inline content addition - more compact -->
      <div class="mt-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
        <div v-if="!showSelection && showInsertMenuAt === null" class="flex flex-wrap items-center gap-2">
          <!-- Quick add buttons for common content types - more compact -->
          <button v-for="type in quickAddTypes" :key="type.value"
            :disabled="isMaxContentReached"
            class="flex items-center gap-1.5 rounded-md border border-dashed border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/50"
            @click="handleElementCreate(type.value)">
            <component :is="type.icon" class="h-3.5 w-3.5" />
            <span>{{ type.label }}</span>
            <Badge v-if="type.isNew" variant="success" size="tiny">
              {{ $t('rich-content.new_badge') }}
            </Badge>
          </button>

          <!-- More content types button -->
          <button :disabled="isMaxContentReached"
            :title="isMaxContentReached ? $t('rich-content.max_blocks_reached') : $t('rich-content.more_content_types')"
            class="flex items-center gap-1.5 rounded-md border border-dashed border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/50"
            @click="showSelection = true">
            <IFluentAdd24Regular class="h-3.5 w-3.5" />
            <span>{{ $t('rich-content.more_types') }}</span>
          </button>
        </div>

      </div>
    </div>

    <!-- Categorized picker with a live rendered preview, replacing the old flat grid. -->
    <BlockPickerDialog
      :open="showSelection || showInsertMenuAt !== null"
      :insert-label="showInsertMenuAt !== null ? $t('rich-content.insert_content_block') : undefined"
      @update:open="(val) => !val && closeInsertMenus()"
      @select="handleInsertContentType"
    />
  </div>
</template>

<script setup lang="ts">
import { moveArrayElement, useSortable } from '@vueuse/integrations/useSortable';
import { computed, nextTick, ref, onUnmounted, onMounted, watch } from 'vue';
import { useManualRefHistory } from '@vueuse/core';

import FadeTransition from '../Transitions/FadeTransition.vue';

import BlockPickerDialog from './BlockPickerDialog.vue';
import RCBlockCard from './Editor/RCBlockCard.vue';
import RCFullscreenEditor from './Editor/Fullscreen/RCFullscreenEditor.vue';
import RCInsertAffordance from './Editor/RCInsertAffordance.vue';
import { getQuickAddTypes } from './Editor/quickAddTypes';
import { createContentItem, type ContentPart } from './Types';
import { resolveBands, type BandResolution } from './bandLayout';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Badge } from '@/Components/ui/badge';
import { Skeleton } from '@/Components/ui/skeleton';
import IFluentAdd24Regular from '~icons/fluent/add24-regular';
import IFluentArrowUndo24Filled from '~icons/fluent/arrow-undo24-filled';
import IFluentArrowRedo24Filled from '~icons/fluent/arrow-redo24-filled';
import IFluentFullScreenMaximize24Regular from '~icons/fluent/full-screen-maximize24-regular';

const props = defineProps<{
  maxContentBlocks?: number;
  /** Tenant the page/news article being edited belongs to — for server-resolved previews. */
  tenantId?: number | null;
}>();

const contents = defineModel<ContentPart[]>('contents');

const bandMap = computed<Map<ContentPart, BandResolution>>(() => resolveBands(contents.value ?? []));

defineEmits<(e: 'save') => void>();

/**
 * Ensure all content items have unique keys for TransitionGroup.
 * Items from templates or database may not have keys, so we generate them.
 * We mutate in place to avoid triggering unnecessary re-renders that would
 * unfocus editors.
 */
const ensureKeys = () => {
  if (!contents.value) return;
  contents.value.forEach((item, index) => {
    if (!item.key && !item.id) {
      // Mutate in place - don't trigger a full re-render
      item.key = `generated-${Date.now()}-${index}-${Math.random().toString(36).substring(7)}`;
    }
  });
};

// Run once on mount to ensure initial content has keys
onMounted(() => {
  ensureKeys();

  // Long pages read better collapsed by default — only the first block (usually the
  // one being actively edited) starts open.
  if ((contents.value?.length ?? 0) > 4) {
    contents.value?.forEach((item, index) => {
      if (index > 0) collapsedKeys.value.add(getBlockKey(item));
    });
  }
});

const { history, commit, undo, redo, canUndo, canRedo } = useManualRefHistory(contents, { clone: true, capacity: 30 });

// TransitionGroup component ref - we extract $el to get the DOM element
const sortableEl = ref<{ $el: HTMLElement } | null>(null);
// Ref for the actual DOM element used by useSortable
const el = ref<HTMLElement | null>(null);

const showHistory = ref(false);
const showSelection = ref(false);
const showInsertMenuAt = ref<number | null>(null);
const isInitialLoading = ref(true);
const isFullscreenOpen = ref(false);
const isDragging = ref(false);
// Per-block preview mode / collapse tracking, keyed by block id or generated key
const blocksInPreviewMode = ref(new Set<string | number>());
const collapsedKeys = ref(new Set<string | number>());

// Cleanup timeout to prevent memory leaks
let loadingTimeout: NodeJS.Timeout | null = null;

// Show loading state briefly to indicate the component is ready
loadingTimeout = setTimeout(() => {
  isInitialLoading.value = false;
}, 300);

// Clean up timeout on component unmount
onUnmounted(() => {
  if (loadingTimeout) {
    clearTimeout(loadingTimeout);
    loadingTimeout = null;
  }
});

// Quick add types for common content
const quickAddTypes = computed(getQuickAddTypes);

// Check if max content blocks limit would be exceeded
const isMaxContentReached = computed(() => {
  if (!props.maxContentBlocks) return false;
  return (contents.value?.length || 0) >= props.maxContentBlocks;
});

function handleElementCreate(selectedContent: string) {
  // Check if max content blocks limit would be exceeded
  if (props.maxContentBlocks && (contents.value?.length || 0) >= props.maxContentBlocks) {
    showSelection.value = false;
    return;
  }

  commit();
  const newItem = createContentItem(selectedContent);
  // Always start expanded for seamless editing
  newItem.expanded = true;
  contents.value?.push(newItem);
  showHistory.value = true;
  nextTick(() => commit());
  closeInsertMenus();
}

function handleElementRemove(index: number) {
  commit();
  contents.value?.splice(index, 1);
  showHistory.value = true;
  nextTick(() => commit());
}

function insertContentAt(contentType: string, insertAtIndex: number) {
  // Check if max content blocks limit would be exceeded
  if (props.maxContentBlocks && (contents.value?.length || 0) >= props.maxContentBlocks) {
    return;
  }

  commit();
  const newItem = createContentItem(contentType);
  // Always start expanded for seamless editing
  newItem.expanded = true;
  contents.value?.splice(insertAtIndex, 0, newItem);
  showHistory.value = true;
  nextTick(() => commit());
}

function handleInsertContentType(contentType: string) {
  if (showInsertMenuAt.value !== null) {
    // Insert at specific position
    insertContentAt(contentType, showInsertMenuAt.value);
    closeInsertMenus();
  }
  else {
    // Insert at end (regular add)
    handleElementCreate(contentType);
  }
}

function closeInsertMenus() {
  showSelection.value = false;
  showInsertMenuAt.value = null;
}

// Sortable instance cleanup
let stopSortable: (() => void) | null = null;

watch(() => sortableEl.value, (newRef) => {
  // Update the el ref for useSortable
  el.value = newRef?.$el ?? null;
}, { immediate: true });

watch(el, (newEl) => {
  // Stop previous sortable instance
  if (stopSortable) {
    stopSortable();
    stopSortable = null;
  }

  if (newEl) {
    const { stop } = useSortable(newEl, contents, {
      handle: '.rc-drag-handle',
      animation: 150,
      ghostClass: 'opacity-50',
      onStart: () => {
        // Visually collapse every block's body for the duration of the drag (pure CSS,
        // via .rc-dragging below) rather than mutating collapse state — large blocks
        // were previously impossible to drag because there was nothing to grab outside
        // their (also huge) rendered content.
        isDragging.value = true;
      },
      onEnd: () => {
        isDragging.value = false;
      },
      onUpdate: (e: any) => {
        if (!contents.value || e.oldIndex === undefined || e.newIndex === undefined) return;
        commit();
        moveArrayElement(contents.value, e.oldIndex, e.newIndex);
        showHistory.value = true;
        nextTick(() => commit());
      },
    });
    stopSortable = stop;
  }
}, { immediate: true });

// Per-block preview mode / collapse helpers
function getBlockKey(content: ContentPart): string | number {
  return content.id ?? content.key ?? '';
}

function isBlockInPreviewMode(content: ContentPart): boolean {
  return blocksInPreviewMode.value.has(getBlockKey(content));
}

function setBlockPreviewMode(content: ContentPart, value: boolean) {
  const key = getBlockKey(content);
  if (value) blocksInPreviewMode.value.add(key);
  else blocksInPreviewMode.value.delete(key);
}

function isBlockCollapsed(content: ContentPart): boolean {
  return collapsedKeys.value.has(getBlockKey(content));
}

function setBlockCollapsed(content: ContentPart, value: boolean) {
  const key = getBlockKey(content);
  if (value) collapsedKeys.value.add(key);
  else collapsedKeys.value.delete(key);
}

function collapseAll() {
  collapsedKeys.value = new Set((contents.value ?? []).map(getBlockKey));
}

function expandAll() {
  collapsedKeys.value = new Set();
}

// Cleanup on unmount
onUnmounted(() => {
  if (stopSortable) stopSortable();
});
</script>

<style scoped>
/* Visually collapse every block body during a drag (see onStart/onEnd above) without
   touching each block's own collapsed state — :deep() is needed since the body lives
   inside the RCBlockCard child component, not this component's own template. */
.rc-dragging :deep([data-rc-block-body]) {
  max-height: 0;
  overflow: hidden;
}
</style>
