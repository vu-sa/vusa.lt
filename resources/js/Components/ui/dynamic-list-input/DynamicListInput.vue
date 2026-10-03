<template>
  <div class="space-y-3" data-dynamic-list>
    <!-- Empty state -->
    <div
      v-if="items.length === 0"
      class="flex flex-col items-center justify-center border border-dashed border-border bg-secondary/20 p-6 text-center"
    >
      <component :is="emptyIcon" v-if="emptyIcon" class="mb-2 size-8 text-muted-foreground" />
      <p class="text-sm text-muted-foreground">
        {{ emptyText }}
      </p>
      <Button type="button" variant="outline" size="sm" class="mt-3" @click="addItem">
        <Plus class="mr-2 size-4" />
        {{ addFirstText }}
      </Button>
    </div>

    <!-- Items list -->
    <template v-else>
      <TransitionGroup name="list" tag="div" class="space-y-3">
        <div
          v-for="(item, index) in items"
          :key="itemKeys[index]"
          class="group relative border border-border bg-secondary/30 transition-all hover:border-foreground/30"
          :class="[
            { 'border-brand bg-brand/5': draggedIndex === index },
            compact ? 'py-3 pl-7 pr-8' : 'p-4',
          ]"
        >
          <!-- Compact: hover-revealed drag rail on the left edge -->
          <div
            v-if="compact"
            class="absolute inset-y-0 left-0 flex w-6 shrink-0 cursor-grab items-center justify-center border-r border-transparent text-muted-foreground opacity-0 transition-opacity hover:border-border hover:bg-secondary active:cursor-grabbing group-hover:opacity-100"
            draggable="true"
            @dragstart="handleDragStart(index, $event)"
            @dragend="handleDragEnd"
            @dragover.prevent="handleDragOver(index)"
            @drop="handleDrop(index)"
          >
            <GripVertical class="size-3.5" />
          </div>

          <!-- Compact: delete moves to a hover-revealed top-right icon -->
          <Button
            v-if="compact"
            type="button"
            variant="ghost"
            size="icon"
            class="absolute right-1.5 top-1.5 size-6 shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
            :class="items.length === 1 && !allowEmpty ? 'text-muted-foreground/40 cursor-not-allowed' : 'text-muted-foreground hover:text-destructive'"
            :disabled="items.length === 1 && !allowEmpty"
            @click="removeItem(index)"
          >
            <Trash2 class="size-3.5" />
          </Button>

          <!-- Non-compact: header row -->
          <div v-if="!compact" class="mb-3 flex items-center gap-2">
            <!-- Drag handle -->
            <div
              class="flex size-7 shrink-0 cursor-grab items-center justify-center border border-border bg-background text-muted-foreground transition-colors hover:bg-secondary active:cursor-grabbing"
              draggable="true"
              @dragstart="handleDragStart(index, $event)"
              @dragend="handleDragEnd"
              @dragover.prevent="handleDragOver(index)"
              @drop="handleDrop(index)"
            >
              <GripVertical class="size-4" />
            </div>

            <!-- Item number -->
            <span class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
              #{{ index + 1 }}
            </span>

            <!-- Delete button -->
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="ml-auto size-7 shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
              :class="items.length === 1 && !allowEmpty ? 'text-muted-foreground/40 cursor-not-allowed' : 'text-muted-foreground hover:text-destructive'"
              :disabled="items.length === 1 && !allowEmpty"
              @click="removeItem(index)"
            >
              <Trash2 class="size-4" />
            </Button>
          </div>

          <!-- Custom item content via slot -->
          <slot name="item" :item :index :update="(newItem: T) => updateItem(index, newItem)" />
        </div>
      </TransitionGroup>

      <!-- Add button -->
      <Button
        v-if="!max || items.length < max"
        type="button"
        variant="outline"
        size="sm"
        class="w-full border-dashed"
        @click="addItem"
      >
        <Plus class="mr-2 size-4" />
        {{ addText }}
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts" generic="T">
import { ref, watch, type Component } from 'vue';
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

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
  /**
   * Drop the full-width header row (drag handle + #N + delete) above every item in
   * favor of a hover-revealed left rail + top-right delete icon — reclaims vertical
   * space for editors that stack many short items (accordion entries, carousel slides).
   */
  compact?: boolean;
}>(), {
  allowEmpty: false,
  emptyText: 'No items added yet',
  addFirstText: 'Add first item',
  addText: 'Add item',
  compact: false,
});

const items = defineModel<T[]>({ default: () => [] });

// Generate stable keys for list items
const itemKeys = ref<number[]>([]);
let keyCounter = 0;

function resyncKeys(currentLength: number) {
  if (itemKeys.value.length === currentLength) return;
  if (itemKeys.value.length < currentLength) {
    for (let i = itemKeys.value.length; i < currentLength; i++) {
      itemKeys.value.push(keyCounter++);
    }
  }
  else {
    itemKeys.value.length = currentLength;
  }
}

// Keep keys in sync whenever the items array changes length — including a wholesale
// replacement from outside this component (form load, undo/redo), which previously
// left itemKeys stale since only additions were handled here.
watch(() => items.value?.length, (newLen) => {
  if (newLen === undefined) return;
  resyncKeys(newLen);
}, { immediate: true });

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
