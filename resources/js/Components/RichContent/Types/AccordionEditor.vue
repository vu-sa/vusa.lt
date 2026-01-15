<template>
  <DynamicListInput
    v-model="modelValue"
    :create-item="createItem"
    :empty-text="$t('rich-content.no_accordion_items')"
    :add-first-text="$t('rich-content.add_first_accordion_item')"
    :add-text="$t('rich-content.add_accordion_item')"
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
          <OriginalTipTap 
            :model-value="item.content" 
            @update:model-value="update({ ...item, content: $event })" 
          />
        </Field>
      </div>
    </template>
  </DynamicListInput>
</template>

<script setup lang="ts">
import type { ShadcnAccordion } from '@/Types/contentParts';
import OriginalTipTap from '@/Components/TipTap/OriginalTipTap.vue';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';

const modelValue = defineModel<ShadcnAccordion['json_content']>();

function createItem(): ShadcnAccordion['json_content'][number] {
  return {
    label: "",
    content: {} as ShadcnAccordion['json_content'][number]['content'],
  };
}
</script>
