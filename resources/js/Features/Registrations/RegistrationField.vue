<template>
  <div class="space-y-2" :data-registration-field="field.id">
    <Label :for="controlId" class="font-semibold">
      {{ localizedLabel(field.label, locale) }}<span v-if="field.is_required" class="text-destructive"> *</span>
    </Label>
    <Select v-if="['enum', 'select'].includes(field.type)" :model-value="selectValue" @update:model-value="updateSelection">
      <SelectTrigger :id="controlId" class="w-full" v-bind="accessibility">
        <SelectValue :placeholder="localizedLabel(field.placeholder, locale)" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem v-for="option in fieldOptions(field)" :key="option.value" :value="option.value">
          {{ localizedLabel(option.label, locale) }}
        </SelectItem>
      </SelectContent>
    </Select>
    <Checkbox v-else-if="field.type === 'boolean'" :id="controlId" :model-value="modelValue === true" v-bind="accessibility"
      @update:model-value="updateSelection($event === true)" />
    <Textarea v-else-if="field.type === 'text' || field.subtype === 'textarea'" :id="controlId" :model-value="textValue"
      :placeholder="localizedLabel(field.placeholder, locale)" v-bind="accessibility" @update:model-value="emit('update:modelValue', String($event))" @blur="emit('validate')" />
    <Input v-else :id="controlId" :model-value="textValue" :type="inputType" :step="field.type === 'number' ? 'any' : undefined"
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

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
const selectValue = computed(() => typeof props.modelValue === 'string' || typeof props.modelValue === 'number' ? props.modelValue : undefined);
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
  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    emit('update:modelValue', value);
    emit('validate');
  }
}
</script>
