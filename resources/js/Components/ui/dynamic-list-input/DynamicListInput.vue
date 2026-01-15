<template>
  <div class="space-y-3" data-dynamic-list>
    <!-- Empty state -->
    <div v-if="items.length === 0"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-200 p-6 text-center dark:border-zinc-700">
      <component :is="emptyIcon" v-if="emptyIcon" class="mb-2 h-8 w-8 text-zinc-400 dark:text-zinc-500" />
      <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ emptyText }}
      </p>
      <Button type="button" variant="outline" size="sm" class="mt-3" @click="addItem">
        <IFluentAdd24Regular class="mr-2 h-4 w-4" />
        {{ addFirstText }}
      </Button>
    </div>

    <!-- Items list -->
    <template v-else>
      <TransitionGroup name="list" tag="div" class="space-y-3">
        <div v-for="(item, index) in items" :key="itemKeys[index]"
          class="group relative rounded-lg border border-dashed border-zinc-200 bg-zinc-50/50 p-4 transition-all hover:border-solid hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800/30 dark:hover:border-zinc-600"
          :class="{ 'border-primary bg-primary/5': draggedIndex === index }">
          
          <!-- Header with drag handle and controls -->
          <div class="mb-3 flex items-center gap-2">
            <!-- Drag handle -->
            <div
              class="flex h-7 w-7 shrink-0 cursor-grab items-center justify-center rounded bg-zinc-100 text-sm font-medium text-zinc-500 transition-colors hover:bg-zinc-200 active:cursor-grabbing dark:bg-zinc-800 dark:hover:bg-zinc-700"
              draggable="true"
              @dragstart="handleDragStart(index, $event)"
              @dragend="handleDragEnd"
              @dragover.prevent="handleDragOver(index)"
              @drop="handleDrop(index)">
              <IFluentReOrderDotsVertical24Regular class="h-4 w-4" />
            </div>
            
            <!-- Item number -->
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
              #{{ index + 1 }}
            </span>

            <!-- Delete button -->
            <Button 
              type="button" 
              variant="ghost" 
              size="icon"
              class="ml-auto h-7 w-7 shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
              :class="items.length === 1 && !allowEmpty ? 'text-zinc-400 cursor-not-allowed' : 'text-zinc-500 hover:text-red-600 dark:hover:text-red-400'"
              :disabled="items.length === 1 && !allowEmpty"
              @click="removeItem(index)">
              <IFluentDelete24Regular class="h-4 w-4" />
            </Button>
          </div>

          <!-- Custom item content via slot -->
          <slot name="item" :item="item" :index="index" :update="(newItem: T) => updateItem(index, newItem)" />
        </div>
      </TransitionGroup>

      <!-- Add button -->
      <Button 
        v-if="!max || items.length < max" 
        type="button" 
        variant="outline" 
        size="sm" 
        class="w-full border-dashed"
        @click="addItem">
        <IFluentAdd24Regular class="mr-2 h-4 w-4" />
        {{ addText }}
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts" generic="T">
import { ref, watch, type Component } from 'vue';
import { Button } from '@/Components/ui/button';
import IFluentAdd24Regular from '~icons/fluent/add24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentReOrderDotsVertical24Regular from '~icons/fluent/re-order-dots-vertical24-regular';

const props = withDefaults(defineProps<{
  /** Maximum number of items allowed */
  max?: number;
  /** Allow removing all items (empty list) */
  allowEmpty?: boolean;
  /** Text for empty state */
  emptyText?: string;
  /** Text for "Add first" button */
  addFirstText?: string;
  /** Text for "Add" button */
  addText?: string;
  /** Icon for empty state */
  emptyIcon?: Component;
  /** Factory function to create new items */
  createItem: () => T;
}>(), {
  allowEmpty: false,
  emptyText: 'No items added yet',
  addFirstText: 'Add first item',
  addText: 'Add item',
});

const items = defineModel<T[]>({ required: true });

// Generate stable keys for list items
const itemKeys = ref<number[]>([]);
let keyCounter = 0;

// Initialize keys
watch(() => items.value?.length, (newLen, oldLen) => {
  if (newLen === undefined) return;
  oldLen = oldLen ?? 0;
  
  if (newLen > oldLen) {
    // Items added - add new keys
    for (let i = oldLen; i < newLen; i++) {
      itemKeys.value.push(keyCounter++);
    }
  }
}, { immediate: true });

// Initialize keys on mount
if (itemKeys.value.length === 0 && items.value?.length > 0) {
  for (let i = 0; i < items.value.length; i++) {
    itemKeys.value.push(keyCounter++);
  }
}

// Drag state
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

// CRUD operations
function addItem() {
  if (props.max && items.value.length >= props.max) return;
  
  const newItem = props.createItem();
  items.value = [...items.value, newItem];
  itemKeys.value.push(keyCounter++);
}

function updateItem(index: number, newItem: T) {
  const newItems = [...items.value];
  newItems[index] = newItem;
  items.value = newItems;
}

function removeItem(index: number) {
  if (items.value.length <= 1 && !props.allowEmpty) return;

  const newItems = [...items.value];
  newItems.splice(index, 1);
  itemKeys.value.splice(index, 1);
  items.value = newItems;
}

// Drag and drop
function handleDragStart(index: number, event: DragEvent) {
  draggedIndex.value = index;
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));
  }
}

function handleDragEnd() {
  draggedIndex.value = null;
  dragOverIndex.value = null;
}

function handleDragOver(index: number) {
  dragOverIndex.value = index;
}

function handleDrop(targetIndex: number) {
  if (draggedIndex.value === null || draggedIndex.value === targetIndex) {
    return;
  }

  const newItems = [...items.value];
  const newKeys = [...itemKeys.value];

  // Remove dragged item
  const [draggedItem] = newItems.splice(draggedIndex.value, 1);
  const [draggedKey] = newKeys.splice(draggedIndex.value, 1);

  // Insert at target position (items are guaranteed to exist since we checked draggedIndex)
  if (draggedItem !== undefined && draggedKey !== undefined) {
    newItems.splice(targetIndex, 0, draggedItem);
    newKeys.splice(targetIndex, 0, draggedKey);

    itemKeys.value = newKeys;
    items.value = newItems;
  }

  draggedIndex.value = null;
  dragOverIndex.value = null;
}
</script>

<style scoped>
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.2s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}

.list-leave-active {
  position: absolute;
}
</style>
