<template>
  <div class="flex flex-col gap-5">
    <Field>
      <FieldLabel>{{ $t('rich-content.steps_columns') }}</FieldLabel>
      <Select :model-value="String(options?.columns ?? 3)" @update:model-value="setColumns">
        <SelectTrigger>
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="2">2</SelectItem>
          <SelectItem value="3">3</SelectItem>
          <SelectItem value="4">4</SelectItem>
        </SelectContent>
      </Select>
    </Field>

    <RCSectionOptions v-model="options" />

    <DynamicListInput
      v-model="content"
      :create-item
      :empty-text="$t('rich-content.no_steps')"
      :add-first-text="$t('rich-content.add_first_step')"
      :add-text="$t('rich-content.add_step')"
      allow-empty>
      <template #item="{ item, update }">
        <div class="flex flex-col gap-4">
          <Field>
            <FieldLabel>{{ $t('rich-content.step_title') }}</FieldLabel>
            <Input
              :model-value="item.title"
              type="text"
              :placeholder="$t('rich-content.enter_step_title')"
              @update:model-value="update({ ...item, title: String($event) })"
            />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.step_text') }}</FieldLabel>
            <Textarea
              :model-value="item.text"
              :rows="2"
              :placeholder="$t('rich-content.enter_step_text')"
              @update:model-value="update({ ...item, text: String($event) })"
            />
          </Field>
        </div>
      </template>
    </DynamicListInput>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import RCSectionOptions from '../Editor/RCSectionOptions.vue';

import type { ProcessSteps } from '@/Types/contentParts';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';

const content = defineModel<ProcessSteps['json_content']>();
const options = defineModel<ProcessSteps['options']>('options');

// The Select speaks strings; the display coerces too, but storing a number keeps the data honest.
function setColumns(value: unknown): void {
  options.value = { ...options.value, columns: Number(value) as 2 | 3 | 4 };
}

function createItem(): ProcessSteps['json_content'][number] {
  return { title: '', text: '' };
}
</script>
