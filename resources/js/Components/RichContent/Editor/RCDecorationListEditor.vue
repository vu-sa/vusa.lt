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
          <div class="grid grid-cols-2 gap-4">
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
            <Field>
              <FieldLabel>{{ $t('rich-content.decoration_color') }}</FieldLabel>
              <Select :model-value="item.color || 'vusa-red'" @update:model-value="update({ ...item, color: $event })">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="vusa-red">{{ $t('rich-content.vusa_red') }}</SelectItem>
                  <SelectItem value="vusa-yellow">{{ $t('rich-content.vusa_yellow') }}</SelectItem>
                  <SelectItem value="zinc">{{ $t('rich-content.zinc') }}</SelectItem>
                </SelectContent>
              </Select>
            </Field>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <Field>
              <FieldLabel>{{ $t('rich-content.decoration_opacity') }}</FieldLabel>
              <Input
                :model-value="item.opacity"
                type="number"
                min="0"
                max="100"
                @update:model-value="update({ ...item, opacity: Number($event) })"
              />
            </Field>
            <div class="flex items-center gap-3 pt-6">
              <Switch
                :model-value="item.rotation"
                @update:model-value="update({ ...item, rotation: $event })"
              />
              <span class="text-sm text-zinc-700 dark:text-zinc-300">
                {{ $t('rich-content.enable_rotation') }}
              </span>
            </div>
          </div>
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
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

defineProps<{
  label?: string;
}>();

const decorations = defineModel<DecorationConfig[]>({ required: true });

function createDecoration(): DecorationConfig {
  return {
    type: 'line',
    position: 'top-right',
    size: 'md',
    color: 'vusa-red',
    opacity: 60,
    rotation: false,
  };
}
</script>
