<template>
  <DynamicListInput
    v-model="modelValue"
    :max="max"
    :allow-empty="false"
    :empty-text="emptyText"
    :add-first-text="addFirstText"
    :add-text="addText"
    :empty-icon="emptyIcon"
    :create-item="() => ''">
    <template #item="{ item, index, update }">
      <component 
        :is="inputType === 'textarea' ? Textarea : Input" 
        :model-value="item"
        :placeholder="placeholder?.replace('{n}', String(index + 1)) || `${$t('Punktas')} ${index + 1}...`"
        :class="inputType === 'textarea' ? 'min-h-9 resize-none' : ''"
        :rows="inputType === 'textarea' ? 1 : undefined" 
        @update:model-value="update($event)"
        @keydown.enter.prevent="handleEnter(index)"
        @keydown.backspace="handleBackspace(index, item)" 
      />
    </template>
  </DynamicListInput>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

const props = withDefaults(defineProps<{
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
});

const modelValue = defineModel<string[]>({ required: true });

// Keyboard: Enter adds new item after current
function handleEnter(index: number) {
  if (!props.max || modelValue.value.length < props.max) {
    const newValue = [...modelValue.value];
    newValue.splice(index + 1, 0, '');
    modelValue.value = newValue;
    
    // Focus new input
    setTimeout(() => {
      const container = document.querySelector('[data-dynamic-list]');
      const inputs = container?.querySelectorAll('input, textarea');
      (inputs?.[index + 1] as HTMLInputElement)?.focus();
    }, 50);
  }
}

// Keyboard: Backspace on empty removes item and focuses previous
function handleBackspace(index: number, value: string) {
  if (value === '' && index > 0 && modelValue.value.length > 1) {
    const newValue = [...modelValue.value];
    newValue.splice(index, 1);
    modelValue.value = newValue;
    
    // Focus previous input
    setTimeout(() => {
      const container = document.querySelector('[data-dynamic-list]');
      const inputs = container?.querySelectorAll('input, textarea');
      (inputs?.[index - 1] as HTMLInputElement)?.focus();
    }, 50);
  }
}
</script>
