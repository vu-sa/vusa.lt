<template>
  <Field>
    <FieldLabel>{{ label ?? $t('rich-content.image_decorations') }}</FieldLabel>
    <DynamicListInput
      v-model="decorations"
      :create-item="createDecoration"
      :empty-text="$t('rich-content.no_decorations')"
      :add-first-text="$t('rich-content.add_first_decoration')"
      :add-text="$t('rich-content.add_decoration')">
      <template #item="{ item, update }">
        <div class="flex flex-col gap-3">
          <div class="grid grid-cols-2 gap-4">
            <Field>
              <FieldLabel>{{ $t('rich-content.decoration_type') }}</FieldLabel>
              <Select :model-value="item.type" @update:model-value="update({ ...item, type: $event })">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="line">{{ $t('rich-content.line') }}</SelectItem>
                  <SelectItem value="circle">{{ $t('rich-content.circle') }}</SelectItem>
                  <SelectItem value="square">{{ $t('rich-content.square') }}</SelectItem>
                </SelectContent>
              </Select>
            </Field>
            <Field>
              <FieldLabel>{{ $t('rich-content.decoration_position') }}</FieldLabel>
              <Select :model-value="item.position" @update:model-value="update({ ...item, position: $event })">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="top-left">{{ $t('rich-content.top_left') }}</SelectItem>
                  <SelectItem value="top-right">{{ $t('rich-content.top_right') }}</SelectItem>
                  <SelectItem value="bottom-left">{{ $t('rich-content.bottom_left') }}</SelectItem>
                  <SelectItem value="bottom-right">{{ $t('rich-content.bottom_right') }}</SelectItem>
                </SelectContent>
              </Select>
            </Field>
          </div>
          <Field>
            <FieldLabel>{{ $t('rich-content.decoration_size') }}</FieldLabel>
            <Select :model-value="item.size" @update:model-value="update({ ...item, size: $event })">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="sm">{{ $t('rich-content.small') }}</SelectItem>
                <SelectItem value="md">{{ $t('rich-content.medium') }}</SelectItem>
                <SelectItem value="lg">{{ $t('rich-content.large') }}</SelectItem>
              </SelectContent>
            </Select>
          </Field>
        </div>
      </template>
    </DynamicListInput>
  </Field>
</template>

<script setup lang="ts">
/**
 * Shared decorative-shape (line/circle/square accent) editor for any image that
 * renders through `ImageWithDecorations` — originally hero-only, now also used by
 * content-grid's `image` cell. One implementation so the two can't drift.
 */
import { trans as $t } from 'laravel-vue-i18n';

import type { DecorationConfig } from '@/Types/contentParts';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

defineProps<{
  label?: string;
}>();

const decorations = defineModel<DecorationConfig[]>({ required: true });

function createDecoration(): DecorationConfig {
  return {
    type: 'line',
    position: 'top-right',
    size: 'md',
  };
}
</script>
