<template>
  <div class="flex flex-col gap-5">
    <RCSectionOptions v-model="options" />

    <!-- Stats list -->
    <DynamicListInput
      v-model="content"
      :create-item
      :empty-text="$t('rich-content.no_stats')"
      :add-first-text="$t('rich-content.add_first_stat')"
      :add-text="$t('rich-content.add_stat')"
      allow-empty>
      <template #item="{ item, update }">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Field>
            <FieldLabel>{{ $t('rich-content.stat_label') }}</FieldLabel>
            <Input
              :model-value="item.label"
              type="text"
              :placeholder="$t('rich-content.enter_stat_label')"
              @update:model-value="update({ ...item, label: $event })"
            />
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.stat_number') }}</FieldLabel>
            <NumberField
              :model-value="item.endNumber"
              @update:model-value="update({ ...item, endNumber: $event })"
            />
          </Field>
        </div>
      </template>
    </DynamicListInput>
  </div>
</template>

<script setup lang="ts">
import type { NumberStatSection } from '@/Types/contentParts';
import RCSectionOptions from '../Editor/RCSectionOptions.vue';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';

const content = defineModel<NumberStatSection['json_content']>();
const options = defineModel<NumberStatSection['options']>('options');

function createItem(): NumberStatSection['json_content'][number] {
  return {
    endNumber: 0,
    label: '',
  };
}
</script>
