<template>
  <div class="space-y-2">
    <!-- Empty state -->
    <div v-if="modelValue.length === 0"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 text-center">
      <component :is="emptyIcon" class="mb-2 h-8 w-8 text-muted-foreground/50" />
      <p class="text-sm text-muted-foreground">
        {{ emptyText }}
      </p>
      <Button type="button" variant="outline" size="sm" class="mt-3" @click="addItem">
        <IFluentAdd24Regular class="mr-2 h-4 w-4" />
        {{ addFirstText }}
      </Button>
    </div>

    <!-- Items list -->
    <template v-else>
      <TransitionGroup name="list" tag="div" class="space-y-2">
        <div v-for="(item, index) in modelValue" :key="itemKeys[index]"
          class="group flex items-start gap-2 rounded-lg border border-dashed p-2 transition-all hover:border-solid hover:bg-muted/50"
          :class="{ 'border-primary bg-muted/30': draggedIndex === index }">
          <!-- Number as drag handle -->
          <div
            class="flex h-9 w-8 shrink-0 cursor-grab items-center justify-center rounded-lg bg-muted text-sm font-medium text-muted-foreground transition-colors hover:bg-primary/10 hover:text-primary active:cursor-grabbing"
            draggable="true" @dragstart="handleDragStart(index, $event)" @dragend="handleDragEnd"
            @dragover.prevent="handleDragOver(index)" @drop="handleDrop(index)">
            {{ index + 1 }}
          </div>

          <!-- Input field -->
          <div class="flex-1">
            <component :is="inputType === 'textarea' ? Textarea : Input" :model-value="item"
              :placeholder="placeholder?.replace('{n}', String(index + 1)) || `${$t('Punktas')} ${index + 1}...`"
              :class="inputType === 'textarea' ? 'min-h-9 resize-none' : ''"
              :rows="inputType === 'textarea' ? 1 : undefined" @update:model-value="updateItem(index, $event)"
              @keydown.enter.prevent="handleEnter(index)" @keydown.backspace="handleBackspace(index, item)" />
          </div>

          <!-- Delete button -->
          <Button type="button" variant="ghost" size="icon"
            class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
            :class="modelValue.length === 1 ? 'text-muted-foreground/50 cursor-not-allowed' : 'text-muted-foreground hover:text-red-600'"
            :disabled="modelValue.length === 1" @click="removeItem(index)">
            <IFluentDelete24Regular class="h-4 w-4" />
          </Button>
        </div>
      </TransitionGroup>

      <!-- Add button -->
      <Button v-if="!max || modelValue.length < max" type="button" variant="outline" size="sm" @click="addItem">
        <IFluentAdd24Regular class="mr-2 h-4 w-4" />
        {{ addText }}
      </Button>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, type Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

const props = withDefaults(defineProps<{
  /** The list of string values */
  modelValue: string[];
  /** Maximum number of items allowed */
  max?: number;
  /** Placeholder text for input. Use {n} for item number. */
  placeholder?: string;
  /** Type of input field */
  inputType?: 'input' | 'textarea';
  /** Text for empty state */
  emptyText?: string;
  /** Text for "Add first" button */
  addFirstText?: string;
  /** Text for "Add" button */
  addText?: string;
  /** Icon for empty state */
  emptyIcon?: Component;
}>(), {
  inputType: 'input',
  emptyText: 'Dar nepridėta jokių punktų',
  addFirstText: 'Pridėti pirmą punktą',
  addText: 'Pridėti punktą',
  emptyIcon: () => 'IFluentTextBulletListLtr24Regular',
});

const emit = defineEmits<(e: 'update:modelValue', value: string[]) => void>();

// Generate stable keys for list items
const itemKeys = ref<number[]>([]);
let keyCounter = 0;

// Initialize keys
watch(() => props.modelValue.length, (newLen, oldLen) => {
  if (newLen > oldLen) {
    // Items added - add new keys
    for (let i = oldLen; i < newLen; i++) {
      itemKeys.value.push(keyCounter++);
    }
  } else if (newLen < oldLen) {
    // Items removed - keys are managed by remove function
  }
}, { immediate: true });

// Initialize keys on mount
if (itemKeys.value.length === 0 && props.modelValue.length > 0) {
  for (let i = 0; i < props.modelValue.length; i++) {
    itemKeys.value.push(keyCounter++);
  }
}

// Drag state
const draggedIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

// CRUD operations
function addItem() {
  const newValue = [...props.modelValue, ''];
  itemKeys.value.push(keyCounter++);
  emit('update:modelValue', newValue);

  // Focus the new input after render
  setTimeout(() => {
    const inputs = document.querySelectorAll('[data-ordered-list-input]');
    const newInput = inputs[inputs.length - 1] as HTMLInputElement;
    newInput?.focus();
  }, 50);
}

function updateItem(index: number, value: string) {
  const newValue = [...props.modelValue];
  newValue[index] = value;
  emit('update:modelValue', newValue);
}

function removeItem(index: number) {
  if (props.modelValue.length <= 1) return;

  const newValue = [...props.modelValue];
  newValue.splice(index, 1);
  itemKeys.value.splice(index, 1);
  emit('update:modelValue', newValue);
}

// Keyboard handling
function handleEnter(index: number) {
  if (!props.max || props.modelValue.length < props.max) {
    // Insert new item after current
    const newValue = [...props.modelValue];
    newValue.splice(index + 1, 0, '');
    itemKeys.value.splice(index + 1, 0, keyCounter++);
    emit('update:modelValue', newValue);

    // Focus new input
    setTimeout(() => {
      const inputs = document.querySelectorAll('input, textarea');
      const allInputs = Array.from(inputs).filter(el =>
        el.closest('[data-ordered-list]')
      );
      const newInput = allInputs[index + 1] as HTMLInputElement;
      newInput?.focus();
    }, 50);
  }
}

function handleBackspace(index: number, value: string) {
  if (value === '' && index > 0) {
    removeItem(index);
    // Focus previous input
    setTimeout(() => {
      const inputs = document.querySelectorAll('input, textarea');
      const allInputs = Array.from(inputs).filter(el =>
        el.closest('[data-ordered-list]')
      );
      const prevInput = allInputs[index - 1] as HTMLInputElement;
      prevInput?.focus();
    }, 50);
  }
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

  const newValue = [...props.modelValue];
  const newKeys = [...itemKeys.value];

  // Remove dragged item
  const [draggedItem] = newValue.splice(draggedIndex.value, 1);
  const [draggedKey] = newKeys.splice(draggedIndex.value, 1);

  // Insert at target position
  newValue.splice(targetIndex, 0, draggedItem);
  newKeys.splice(targetIndex, 0, draggedKey);

  itemKeys.value = newKeys;
  emit('update:modelValue', newValue);

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
