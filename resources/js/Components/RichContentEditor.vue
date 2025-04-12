<template>
  <div class="mt-4 flex w-full flex-col gap-4">
    <FadeTransition v-if="showHistory">
      <div class="ml-auto flex items-center gap-2">
        <NButtonGroup size="tiny">
          <NButton :disabled="history?.length < 2" @click="undo()">
            <template #icon>
              <IFluentArrowUndo24Filled />
            </template>
          </NButton>
          <NButton @click="redo()">
            <template #icon>
              <IFluentArrowRedo24Filled />
            </template>
          </NButton>
        </NButtonGroup>
        <p class="text-xs leading-5 text-zinc-400">
          {{ $t('rich-content.restore_content_block_order') }}
        </p>
      </div>
    </FadeTransition>
    <TransitionGroup ref="el" tag="div">
      <div v-for="content, index in contents" :key="content?.id ?? content?.key"
        class="relative grid w-full grid-cols-[24px__1fr] gap-4 border border-zinc-300 p-3 shadow-xs first:rounded-t-lg last:rounded-b-lg dark:border-zinc-700/40 dark:bg-zinc-800/5">
        <NButton class="handle" style="height: 100%;" quaternary size="small">
          <template #icon>
            <IFluentReOrderDotsVertical24Regular />
          </template>
        </NButton>
        <RichContentEditorListElement :id="content?.id" :is-expanded="content?.expanded ?? true"
          :can-delete="contents?.length > 1" :icon="getContentTypeInfo(content?.type).icon"
          :title="getContentTypeInfo(content?.type).label" @up="moveArrayElement(contents, index, index - 1)"
          @down="moveArrayElement(contents, index, index + 1)" @expand="content.expanded = !content?.expanded"
          @remove="handleElementRemove(index)">
          <!-- Dynamically load the appropriate editor component -->
          <div v-show="content.expanded ?? true">
            <ContentEditorFactory v-if="content.expanded" :content="content" />
          </div>
        </RichContentEditorListElement>
      </div>
    </TransitionGroup>
    <div class="mb-6 mt-2 flex w-full gap-2">
      <Button variant="default" :disabled="isMaxContentReached"
        :title="isMaxContentReached ? $t('rich-content.max_blocks_reached') : ''" @click="showSelection = true">
        {{ $t('rich-content.add_content_block') }}
      </Button>
    </div>
    <CardModal v-model:show="showSelection" :title="$t('rich-content.select_content_block')"
      @close="showSelection = false">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
        <Button v-for="type in contentTypes" :key="type.value" variant="outline"
          class="flex flex-col items-center justify-center p-3 h-auto text-center min-h-[120px]"
          @click="handleElementCreate(type.value)">
          <NIcon :component="type.icon" class="text-2xl mb-1" />
          <div class="font-medium">
            {{ $t('rich-content.' + type.value) }}
          </div>
          <p v-if="type.description" class="text-xs text-gray-500 mt-1 line-clamp-2 w-full whitespace-break-spaces">
            {{ type.description }}
          </p>
        </Button>
      </div>
    </CardModal>
  </div>
</template>

<script setup lang="ts">
import { moveArrayElement, useSortable } from "@vueuse/integrations/useSortable";
import { computed, nextTick, ref } from 'vue';
import { useManualRefHistory } from '@vueuse/core';

import CardModal from "./Modals/CardModal.vue";
import FadeTransition from "./Transitions/FadeTransition.vue";
import RichContentEditorListElement from './RichContentEditorListElement.vue';
import ContentEditorFactory from './RichContent/ContentEditorFactory.vue';
import { getAllContentTypes, createContentItem, getContentType } from './RichContent/Types';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  maxContentBlocks?: number;
}>();

const contents = defineModel('contents');

const { history, commit, undo, redo } = useManualRefHistory(contents, { clone: true, capacity: 30 });

const el = ref<HTMLElement | null>(null);
const showHistory = ref(false);
const showSelection = ref(false);

// Get all content types from registry
const contentTypes = getAllContentTypes();

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
  contents.value?.push(createContentItem(selectedContent));
  showHistory.value = true;
  nextTick(() => commit());
  showSelection.value = false;
}

function handleElementRemove(index: number) {
  commit();
  contents.value?.splice(index, 1);
  showHistory.value = true;
  nextTick(() => commit());
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
