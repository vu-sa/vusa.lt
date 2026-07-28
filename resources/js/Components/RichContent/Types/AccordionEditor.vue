<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" />

    <DynamicListInput
      v-model="modelValue"
      :create-item
      :empty-text="$t('rich-content.no_accordion_items')"
      :add-first-text="$t('rich-content.add_first_accordion_item')"
      :add-text="$t('rich-content.add_accordion_item')"
      compact
      allow-empty>
      <template #item="{ item, update }">
        <div class="flex flex-col gap-3">
          <Field>
            <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
            <Input
              :model-value="item.label"
              type="text"
              :placeholder="$t('rich-content.enter_accordion_title')"
              @update:model-value="update({ ...item, label: $event })"
            />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.content') }}</FieldLabel>
            <TiptapEditor
              :model-value="item.content"
              preset="full"
              @update:model-value="update({ ...item, content: $event })"
            />
          </Field>
        </div>
      </template>
    </DynamicListInput>
  </div>
</template>

<script setup lang="ts">
import type { ShadcnAccordion } from '@/Types/contentParts';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const modelValue = defineModel<ShadcnAccordion['json_content']>();
const options = defineModel<NonNullable<ShadcnAccordion['options']>>('options', { default: () => ({}) });

function createItem(): ShadcnAccordion['json_content'][number] {
  return {
    label: '',
    content: {} as ShadcnAccordion['json_content'][number]['content'],
  };
}
</script>
