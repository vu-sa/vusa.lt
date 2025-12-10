<template>
  <div class="mt-4 flex w-full flex-col gap-4">
    <!-- Initial loading state -->
    <div v-if="isInitialLoading" class="space-y-6">
      <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
        <div class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-300 border-r-transparent dark:border-zinc-600"></div>
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
      <FadeTransition v-if="showHistory">
        <div class="ml-auto flex items-center gap-2">
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
    <TransitionGroup ref="el" tag="div" class="space-y-6">
      <div v-for="content, index in contents" :key="content?.id ?? content?.key"
        class="group relative transition-all duration-200 hover:z-10">
        
        <!-- Insert between blocks button - shows on hover between blocks -->
        <div v-if="index > 0" 
          class="absolute -top-3 left-1/2 z-20 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
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
                <NIcon :component="type.icon" class="mr-2 h-4 w-4" />
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
        
        <!-- Drag handle - only visible on hover -->
        <div class="handle absolute -left-8 top-4 opacity-0 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing">
          <div class="flex h-6 w-6 items-center justify-center rounded bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700">
            <IFluentReOrderDotsVertical24Regular class="h-4 w-4 text-zinc-500" />
          </div>
        </div>

        <!-- Content type indicator -->
        <div class="mb-3 flex items-center gap-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
          <NIcon :component="getContentTypeInfo(content?.type).icon" class="h-4 w-4" />
          <span>{{ getContentTypeInfo(content?.type).label }}</span>
          <span v-if="content?.id" class="text-xs text-zinc-400">#{{ content.id }}</span>
          <span v-else class="text-xs text-emerald-600 dark:text-emerald-400">Nauja</span>
          
          <!-- Floating controls - only visible on hover -->
          <div class="ml-auto flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button v-if="index > 0" 
              @click="moveArrayElement(contents, index, index - 1)"
              class="flex h-7 w-7 items-center justify-center rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
              :title="$t('Move up')">
              <IFluentArrowUp24Regular class="h-4 w-4 text-zinc-500" />
            </button>
            <button v-if="index < contents.length - 1" 
              @click="moveArrayElement(contents, index, index + 1)"
              class="flex h-7 w-7 items-center justify-center rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
              :title="$t('Move down')">
              <IFluentArrowDown24Regular class="h-4 w-4 text-zinc-500" />
            </button>
            <button v-if="contents?.length > 1" 
              @click="handleElementRemove(index)"
              class="flex h-7 w-7 items-center justify-center rounded hover:bg-red-100 dark:hover:bg-red-900/20 transition-colors text-red-600 dark:text-red-400"
              :title="$t('Delete')">
              <IFluentDismiss24Regular class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Content editor - always expanded for seamless experience -->
        <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900/50">
          <ContentEditorFactory :content="content" />
        </div>
      </div>
    </TransitionGroup>
    <!-- Inline content addition -->
    <div class="mt-8 border-t border-zinc-200 pt-4 dark:border-zinc-700">
      <div v-if="!showSelection && showInsertMenuAt === null" class="flex items-center gap-3">
        <!-- Quick add buttons for common content types -->
        <button v-for="type in quickAddTypes" :key="type.value"
          @click="handleElementCreate(type.value)"
          :disabled="isMaxContentReached"
          class="flex items-center gap-2 rounded-lg border border-dashed border-zinc-300 px-4 py-3 text-sm font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:bg-zinc-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/50">
          <NIcon :component="type.icon" class="h-4 w-4" />
          <span>{{ type.label }}</span>
        </button>
        
        <!-- More content types button -->
        <button @click="showSelection = true" 
          :disabled="isMaxContentReached"
          :title="isMaxContentReached ? $t('rich-content.max_blocks_reached') : $t('rich-content.more_content_types')"
          class="flex items-center gap-2 rounded-lg border border-dashed border-zinc-300 px-4 py-3 text-sm font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:bg-zinc-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/50">
          <IFluentAdd24Regular class="h-4 w-4" />
          <span>{{ $t('rich-content.more_types') }}</span>
        </button>
      </div>
      
      <!-- Expanded content type selection -->
      <div v-if="showSelection || showInsertMenuAt !== null" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ showInsertMenuAt !== null ? `Insert after block ${showInsertMenuAt}` : $t('rich-content.select_content_block') }}
          </h3>
          <button @click="closeInsertMenus" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
            <IFluentDismiss24Regular class="h-4 w-4" />
          </button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <button v-for="type in contentTypes" :key="type.value"
            @click="handleInsertContentType(type.value)"
            class="flex flex-col items-center gap-2 rounded-lg border border-zinc-200 p-4 text-center transition-colors hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/50">
            <NIcon :component="type.icon" class="h-6 w-6 text-zinc-600 dark:text-zinc-400" />
            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ type.label }}</div>
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
import { moveArrayElement, useSortable } from "@vueuse/integrations/useSortable";
import { computed, nextTick, ref, onUnmounted } from 'vue';
import { useManualRefHistory } from '@vueuse/core';
import { NIcon } from 'naive-ui';

import FadeTransition from "./Transitions/FadeTransition.vue";
import ContentEditorFactory from './RichContent/ContentEditorFactory.vue';
import { getAllContentTypes, createContentItem, getContentType } from './RichContent/Types';
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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';

const props = defineProps<{
  maxContentBlocks?: number;
}>();

const contents = defineModel('contents');

const { history, commit, undo, redo } = useManualRefHistory(contents, { clone: true, capacity: 30 });

const el = ref<HTMLElement | null>(null);
const showHistory = ref(false);
const showSelection = ref(false);
const showInsertMenuAt = ref<number | null>(null);
const isInitialLoading = ref(true);

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
  getContentType('social-embed')
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

function handleElementCreate(selectedContent) {
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
  } else {
    // Insert at end (regular add)
    handleElementCreate(contentType);
  }
}

function closeInsertMenus() {
  showSelection.value = false;
  showInsertMenuAt.value = null;
}

useSortable(el, contents, {
  handle: ".handle",
  animation: 100,
  onUpdate: (e) => {
    commit();
    moveArrayElement(contents.value, e.oldIndex, e.newIndex)
    showHistory.value = true;
    nextTick(() => commit());
  }
});
</script>
