<template>
  <div class="flex flex-col gap-4">
    <Field>
      <FieldLabel>{{ $t('rich-content.height_class') }}</FieldLabel>
      <Select :model-value="image.heightClass || 'h-52'" @update:model-value="emit('update:patch', { heightClass: $event as string })">
        <SelectTrigger><SelectValue /></SelectTrigger>
        <SelectContent>
          <SelectItem value="h-32">
            {{ $t('rich-content.small') }} (h-32)
          </SelectItem>
          <SelectItem value="h-40">
            {{ $t('rich-content.medium_small') }} (h-40)
          </SelectItem>
          <SelectItem value="h-52">
            {{ $t('rich-content.medium') }} (h-52)
          </SelectItem>
          <SelectItem value="h-64">
            {{ $t('rich-content.large') }} (h-64)
          </SelectItem>
        </SelectContent>
      </Select>
    </Field>

    <Field>
      <FieldLabel>{{ $t('rich-content.image_decorations') }}</FieldLabel>
      <DynamicListInput
        :model-value="image.decorations"
        :create-item="createDecoration"
        :empty-text="$t('rich-content.no_decorations')"
        :add-first-text="$t('rich-content.add_first_decoration')"
        :add-text="$t('rich-content.add_decoration')"
        compact
        @update:model-value="emit('update:patch', { decorations: $event })"
      >
        <template #item="{ item, update }">
          <div class="flex flex-col gap-3">
            <div class="grid grid-cols-2 gap-4">
              <Field>
                <FieldLabel>{{ $t('rich-content.decoration_type') }}</FieldLabel>
                <Select :model-value="item.type" @update:model-value="update({ ...item, type: $event })">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="line">
                      {{ $t('rich-content.line') }}
                    </SelectItem>
                    <SelectItem value="circle">
                      {{ $t('rich-content.circle') }}
                    </SelectItem>
                    <SelectItem value="square">
                      {{ $t('rich-content.square') }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </Field>
              <Field>
                <FieldLabel>{{ $t('rich-content.decoration_position') }}</FieldLabel>
                <Select :model-value="item.position" @update:model-value="update({ ...item, position: $event })">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="top-left">
                      {{ $t('rich-content.top_left') }}
                    </SelectItem>
                    <SelectItem value="top-right">
                      {{ $t('rich-content.top_right') }}
                    </SelectItem>
                    <SelectItem value="bottom-left">
                      {{ $t('rich-content.bottom_left') }}
                    </SelectItem>
                    <SelectItem value="bottom-right">
                      {{ $t('rich-content.bottom_right') }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </Field>
            </div>
            <Field>
              <FieldLabel>{{ $t('rich-content.decoration_size') }}</FieldLabel>
              <Select :model-value="item.size" @update:model-value="update({ ...item, size: $event })">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="sm">
                    {{ $t('rich-content.small') }}
                  </SelectItem>
                  <SelectItem value="md">
                    {{ $t('rich-content.medium') }}
                  </SelectItem>
                  <SelectItem value="lg">
                    {{ $t('rich-content.large') }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </Field>
          </div>
        </template>
      </DynamicListInput>
    </Field>
  </div>
</template>

<script setup lang="ts">
import type { PhotoGalleryGrid } from '@/Types/contentParts';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

type GalleryImage = PhotoGalleryGrid['json_content'][number];

defineProps<{ image: GalleryImage }>();
const emit = defineEmits<(e: 'update:patch', patch: Partial<GalleryImage>) => void>();

function createDecoration(): NonNullable<GalleryImage['decorations']>[number] {
  return { type: 'line', position: 'top-right', size: 'md' };
}
</script>
