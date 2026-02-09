<template>
  <div class="mt-4 flex w-full flex-col gap-4">
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

    <template v-else>
      <div class="flex items-center justify-between">
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
        <!-- Global preview toggle -->
        <div class="ml-auto flex items-center gap-2">
          <label class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 cursor-pointer">
            <input v-model="globalPreviewMode" type="checkbox" class="h-3.5 w-3.5 rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500 dark:border-zinc-600">
            {{ $t('rich-content.preview_all') }}
          </label>
        </div>
      </div>

      <!-- Global preview mode - shows all content with proper typography -->
      <div v-if="globalPreviewMode" class="typography mx-auto max-w-3xl rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900/50">
        <RichContentParser :content="(contents as unknown as models.ContentPart[]) ?? []" />
      </div>

      <!-- Editor mode - sortable blocks -->
      <template v-else>
        <!-- Sortable container - ref must be on the direct parent of sortable items -->
        <TransitionGroup ref="sortableEl" tag="div" class="space-y-3 ml-8">
          <div v-for="content, index in contents" :key="content?.id ?? content?.key"
            class="group relative pl-2 border-l-2 border-transparent hover:border-zinc-300 dark:hover:border-zinc-600 transition-all duration-200 hover:z-10">
            <!-- Insert between blocks button - shows on hover between blocks -->
            <div v-if="index > 0"
              class="absolute -top-3 left-1/2 z-20 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <button class="flex h-6 w-6 items-center justify-center rounded-full bg-white border border-zinc-300 shadow-sm hover:shadow-md transition-all dark:bg-zinc-800 dark:border-zinc-600">
                    <IFluentAdd24Regular class="h-3 w-3 text-zinc-600 dark:text-zinc-400" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="center" class="w-56">
                  <DropdownMenuItem
                    v-for="type in quickAddTypes"
                    :key="type.value"
                    @click="insertContentAt(type.value, index)"
                  >
                    <component :is="type.icon" class="mr-2 h-4 w-4" />
                    {{ type.label }}
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="showInsertMenuAt = index; showSelection = true">
                    <IFluentMoreHorizontal24Regular class="mr-2 h-4 w-4" />
                    More content types...
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

            <!-- Drag handle - integrated into left border -->
            <div class="handle absolute -left-6 top-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing">
              <div class="flex h-5 w-5 items-center justify-center rounded bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">
                <IFluentReOrderDotsVertical24Regular class="h-3.5 w-3.5 text-zinc-500" />
              </div>
            </div>

            <!-- Compact header bar integrated with content -->
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/50 overflow-hidden">
              <!-- Minimal header - only type indicator and controls -->
              <div class="flex items-center gap-2 px-3 py-1.5 border-b border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30">
                <component :is="getContentTypeInfo(content?.type).icon" class="h-3.5 w-3.5 text-zinc-500" />
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ getContentTypeInfo(content?.type).label }}</span>
                <span v-if="content?.id" class="text-[10px] text-zinc-400">#{{ content.id }}</span>
                <span v-else class="text-[10px] text-emerald-600 dark:text-emerald-400">{{ $t('New') }}</span>

                <!-- Floating controls - only visible on hover -->
                <div class="ml-auto flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                  <!-- Preview toggle button -->
                  <button
                    class="flex h-6 w-6 items-center justify-center rounded transition-colors"
                    :class="isBlockInPreviewMode(content)
                      ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'
                      : 'hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-500'"
                    :title="isBlockInPreviewMode(content) ? $t('rich-content.switch_to_edit') : $t('rich-content.switch_to_preview')"
                    @click="toggleBlockPreviewMode(content)">
                    <IFluentEdit24Regular v-if="isBlockInPreviewMode(content)" class="h-3.5 w-3.5" />
                    <IFluentEye24Regular v-else class="h-3.5 w-3.5" />
                  </button>
                  <button v-if="index > 0"
                    class="flex h-6 w-6 items-center justify-center rounded hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                    :title="$t('Move up')"
                    @click="contents?.length && moveArrayElement(contents, index, index - 1)">
                    <IFluentArrowUp24Regular class="h-3.5 w-3.5 text-zinc-500" />
                  </button>
                  <button v-if="(contents?.length ?? 0) > index + 1"
                    class="flex h-6 w-6 items-center justify-center rounded hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                    :title="$t('Move down')"
                    @click="contents?.length && moveArrayElement(contents, index, index + 1)">
                    <IFluentArrowDown24Regular class="h-3.5 w-3.5 text-zinc-500" />
                  </button>
                  <button v-if="(contents?.length ?? 0) > 1"
                    class="flex h-6 w-6 items-center justify-center rounded hover:bg-red-100 dark:hover:bg-red-900/20 transition-colors text-red-600 dark:text-red-400"
                    :title="$t('Delete')"
                    @click="handleElementRemove(index)">
                    <IFluentDismiss24Regular class="h-3.5 w-3.5" />
                  </button>
                </div>
              </div>

              <!-- Content editor / preview -->
              <div class="p-3">
                <ContentEditorFactory
                  :content
                  :preview-mode="isBlockInPreviewMode(content)"
                  @update:content="(val) => contents![index] = val" />
              </div>
            </div>
          </div>
        </TransitionGroup>
      </template>
      <!-- Inline content addition - more compact -->
      <div class="mt-4 border-t border-zinc-200 pt-3 dark:border-zinc-700">
        <div v-if="!showSelection && showInsertMenuAt === null" class="flex flex-wrap items-center gap-2">
          <!-- Quick add buttons for common content types - more compact -->
          <button v-for="type in quickAddTypes" :key="type.value"
            :disabled="isMaxContentReached"
            class="flex items-center gap-1.5 rounded-md border border-dashed border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:bg-zinc-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/50"
            @click="handleElementCreate(type.value)">
            <component :is="type.icon" class="h-3.5 w-3.5" />
            <span>{{ type.label }}</span>
          </button>

          <!-- More content types button -->
          <button :disabled="isMaxContentReached"
            :title="isMaxContentReached ? $t('rich-content.max_blocks_reached') : $t('rich-content.more_content_types')"
            class="flex items-center gap-1.5 rounded-md border border-dashed border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:bg-zinc-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/50"
            @click="showSelection = true">
            <IFluentAdd24Regular class="h-3.5 w-3.5" />
            <span>{{ $t('rich-content.more_types') }}</span>
          </button>
        </div>

        <!-- Expanded content type selection -->
        <div v-if="showSelection || showInsertMenuAt !== null" class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
              {{ showInsertMenuAt !== null ? `Insert after block ${showInsertMenuAt}` : $t('rich-content.select_content_block') }}
            </h3>
            <button class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" @click="closeInsertMenus">
              <IFluentDismiss24Regular class="h-4 w-4" />
            </button>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button v-for="type in contentTypes" :key="type.value"
              class="flex flex-col items-center gap-2 rounded-lg border border-zinc-200 p-4 text-center transition-colors hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/50"
              @click="handleInsertContentType(type.value)">
              <component :is="type.icon" class="h-6 w-6 text-zinc-600 dark:text-zinc-400" />
              <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ type.label }}
              </div>
              <p v-if="type.description" class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2">
                {{ type.description }}
              </p>
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { moveArrayElement, useSortable } from '@vueuse/integrations/useSortable';
import { computed, nextTick, ref, onUnmounted, onMounted, watch } from 'vue';
import { useManualRefHistory } from '@vueuse/core';

import FadeTransition from './Transitions/FadeTransition.vue';
import ContentEditorFactory from './RichContent/ContentEditorFactory.vue';
import RichContentParser from './RichContentParser.vue';
import { getAllContentTypes, createContentItem, getContentType, type ContentPart } from './RichContent/Types';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Skeleton } from '@/Components/ui/skeleton';
import IFluentAdd24Regular from '~icons/fluent/add24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUndo24Filled from '~icons/fluent/arrow-undo24-filled';
import IFluentArrowRedo24Filled from '~icons/fluent/arrow-redo24-filled';
import IFluentReOrderDotsVertical24Regular from '~icons/fluent/re-order-dots-vertical24-regular';
import IFluentDismiss24Regular from '~icons/fluent/dismiss24-regular';
import IFluentMoreHorizontal24Regular from '~icons/fluent/more-horizontal24-regular';
import IFluentEye24Regular from '~icons/fluent/eye24-regular';
import IFluentEdit24Regular from '~icons/fluent/edit24-regular';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

const props = defineProps<{
  maxContentBlocks?: number;
}>();

const contents = defineModel<ContentPart[]>('contents');

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
onMounted(() => ensureKeys());

const { history, commit, undo, redo } = useManualRefHistory(contents, { clone: true, capacity: 30 });

// TransitionGroup component ref - we extract $el to get the DOM element
const sortableEl = ref<{ $el: HTMLElement } | null>(null);
// Ref for the actual DOM element used by useSortable
const el = ref<HTMLElement | null>(null);

const showHistory = ref(false);
const showSelection = ref(false);
const showInsertMenuAt = ref<number | null>(null);
const isInitialLoading = ref(true);
const globalPreviewMode = ref(false);
// Per-block preview mode tracking using block keys
const blocksInPreviewMode = ref(new Set<string | number>());

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

// Get all content types from registry
const contentTypes = getAllContentTypes();

// Quick add types for common content
const quickAddTypes = computed(() => [
  getContentType('tiptap'),
  getContentType('shadcn-card'),
  getContentType('image-grid'),
  getContentType('social-embed'),
]);

// Check if max content blocks limit would be exceeded
const isMaxContentReached = computed(() => {
  if (!props.maxContentBlocks) return false;
  return (contents.value?.length || 0) >= props.maxContentBlocks;
});

// Helper function to get content type info
function getContentTypeInfo(type: string) {
  return getContentType(type);
}

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
      handle: '.handle',
      animation: 150,
      ghostClass: 'opacity-50',
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

// Per-block preview mode helpers
function getBlockKey(content: ContentPart): string | number {
  return content.id ?? content.key ?? '';
}

function isBlockInPreviewMode(content: ContentPart): boolean {
  const key = getBlockKey(content);
  return blocksInPreviewMode.value.has(key);
}

function toggleBlockPreviewMode(content: ContentPart) {
  const key = getBlockKey(content);
  if (blocksInPreviewMode.value.has(key)) {
    blocksInPreviewMode.value.delete(key);
  }
  else {
    blocksInPreviewMode.value.add(key);
  }
}

// Initialize useSortable when el is available
watch(el, (newEl) => {
  // Stop previous sortable instance
  if (stopSortable) {
    stopSortable();
    stopSortable = null;
  }

  if (newEl) {
    const { stop } = useSortable(newEl, contents, {
      handle: '.handle',
      animation: 150,
      ghostClass: 'opacity-50',
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

// Cleanup on unmount
onUnmounted(() => {
  if (stopSortable) stopSortable();
});
</script>
