<template>
  <div class="space-y-2" :data-registration-field="field.id">
    <div :class="field.type === 'boolean' && 'flex items-center gap-3'">
      <Checkbox v-if="field.type === 'boolean'" :id="controlId" class="shrink-0" :model-value="modelValue === true" v-bind="accessibility"
        @update:model-value="updateSelection($event === true)" />
      <Label :for="controlId" :class="['font-semibold', field.type === 'boolean' && 'min-h-11 min-w-0 flex-1 cursor-pointer py-3 leading-5']">
        {{ localizedLabel(field.label, locale) }}<span v-if="field.is_required" class="text-destructive"> *</span>
      </Label>
    </div>
    <NativeSelect v-if="['enum', 'select'].includes(field.type)" :id="controlId" :model-value="selectValue" :options="selectOptions"
      :placeholder="localizedLabel(field.placeholder, locale) || $t('common.select')" :placeholder-value="null" :placeholder-selectable="!field.is_required"
      :required="field.is_required" :error v-bind="accessibility" @update:model-value="updateSelection" />
    <Textarea v-else-if="field.type === 'text' || field.subtype === 'textarea'" :id="controlId" :model-value="textValue"
      :placeholder="localizedLabel(field.placeholder, locale)" v-bind="accessibility" @update:model-value="emit('update:modelValue', String($event))" @blur="emit('validate')" />
    <Input v-else-if="field.type !== 'boolean'" :id="controlId" :model-value="textValue" :type="inputType" :step="field.type === 'number' ? 'any' : undefined"
      :placeholder="localizedLabel(field.placeholder, locale)" v-bind="accessibility" @update:model-value="updateInput" @blur="emit('validate')" />
    <div v-if="description" :id="`${controlId}-description`" class="typography text-sm text-muted-foreground" v-html="description" />
    <p v-if="error" :id="`${controlId}-error`" class="text-sm text-destructive" role="alert">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { fieldOptions, localizedLabel, type RegistrationFieldDefinition, type RegistrationValue } from './types';

import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { NativeSelect } from '@/Components/ui/native-select';

const props = defineProps<{
  field: RegistrationFieldDefinition;
  modelValue: RegistrationValue;
  locale: string;
  error?: string;
}>();
const emit = defineEmits<{
  'update:modelValue': [value: RegistrationValue];
  'validate': [];
}>();

const controlId = computed(() => `registration-field-${props.field.id}`);
const description = computed(() => localizedLabel(props.field.description, props.locale));
const textValue = computed(() => typeof props.modelValue === 'boolean' ? '' : (props.modelValue ?? ''));
const selectValue = computed(() => typeof props.modelValue === 'string' || typeof props.modelValue === 'number' ? props.modelValue : null);
const selectOptions = computed(() => fieldOptions(props.field).map(option => ({ value: option.value, label: localizedLabel(option.label, props.locale) })));
const inputType = computed(() => props.field.type === 'email' || props.field.subtype === 'email' ? 'email' : ['number', 'date'].includes(props.field.type) ? props.field.type : 'text');
const accessibility = computed(() => ({
  'aria-required': props.field.is_required,
  'aria-invalid': !!props.error,
  'aria-describedby': [description.value && `${controlId.value}-description`, props.error && `${controlId.value}-error`].filter(Boolean).join(' ') || undefined,
}));

function updateInput(value: string | number) {
  emit('update:modelValue', props.field.type === 'number' && value !== '' ? Number(value) : value);
}

function updateSelection(value: unknown) {
  if (value === null || typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    emit('update:modelValue', value);
    emit('validate');
  }
}
</script>
