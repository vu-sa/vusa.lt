<template>
  <DynamicListInput
    v-model="modelValue"
    :create-item
    :empty-text="$t('rich-content.no_images')"
    :add-first-text="$t('rich-content.add_first_image')"
    :add-text="$t('rich-content.add_image')"
    allow-empty>
    <template #item="{ item, update }">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Field>
          <FieldLabel>{{ $t('rich-content.image_width') }}</FieldLabel>
          <Select
            :model-value="item.colspan"
            @update:model-value="update({ ...item, colspan: $event })">
            <SelectTrigger>
              <SelectValue :placeholder="$t('rich-content.select_width')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in imageGridOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>

        <Field>
          <FieldLabel>{{ $t('rich-content.image') }}</FieldLabel>
          <TiptapImageButton
            v-if="!item.image"
            size="medium"
            @submit="update({ ...item, image: $event })">
            {{ $t('rich-content.select_image') }}
          </TiptapImageButton>
          <div v-else class="flex items-center gap-3">
            <img :src="item.image" class="aspect-video h-16 rounded-lg object-cover">
            <Button variant="ghost" size="sm" class="text-red-600 hover:text-red-700 dark:text-red-400" @click="update({ ...item, image: '' })">
              <IFluentDelete24Regular class="h-4 w-4" />
            </Button>
          </div>
        </Field>
      </div>
    </template>
  </DynamicListInput>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { ImageGrid } from '@/Types/contentParts';
import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Button } from '@/Components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';

const modelValue = defineModel<ImageGrid['json_content']>();

const imageGridOptions = [
  { label: '1/1', value: 'col-span-full' },
  { label: '1/3', value: 'col-span-2' },
  { label: '2/3', value: 'col-span-4' },
  { label: '1/2', value: 'col-span-3' },
];

function createItem(): ImageGrid['json_content'][number] {
  return {
    colspan: 'col-span-2',
    image: '',
  };
}
</script>
