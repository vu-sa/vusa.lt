<template>
  <div class="flex flex-col gap-5">
    <!-- Section options -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Field>
        <FieldLabel>{{ $t('rich-content.title') }}</FieldLabel>
        <Input v-model="options.title" type="text" :placeholder="$t('rich-content.enter_section_title')" />
      </Field>

      <Field>
        <FieldLabel>{{ $t('rich-content.color') }}</FieldLabel>
        <Select v-model="options.color">
          <SelectTrigger>
            <SelectValue :placeholder="$t('rich-content.select_color')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="color in colorOptions" :key="color.value" :value="color.value">
              <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full" :class="color.swatch" />
                {{ color.label }}
              </div>
            </SelectItem>
          </SelectContent>
        </Select>
      </Field>
    </div>

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
import { useColorOptions } from '../composables/useColorOptions';

import type { NumberStatSection } from '@/Types/contentParts';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

const content = defineModel<NumberStatSection['json_content']>();
const options = defineModel<NumberStatSection['options']>('options');

const { colorOptions } = useColorOptions();

function createItem(): NumberStatSection['json_content'][number] {
  return {
    endNumber: 0,
    label: '',
  };
}
</script>
