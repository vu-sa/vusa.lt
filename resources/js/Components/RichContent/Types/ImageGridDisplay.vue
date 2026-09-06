<template>
  <div class="relative space-y-4">
    <RCAddPlaceholder v-if="editable && !element.json_content.length" :label="$t('rich-content.add_image')" @click="addEmptyImage" />
    <div v-else class="grid grid-cols-6 gap-4">
      <div v-for="(image, index) in element.json_content" :key="index"
        :class="[
          getClassesForImage(image.colspan),
          editable && !image.image && 'min-h-32',
        ]"
        class="relative overflow-hidden">
        <img
          :src="image.image"
          class="!size-full rounded-md object-cover"
          :alt="image.alt || image.title || `Image ${index + 1}`"
          :title="image.title || image.alt || `Image ${index + 1}`"
          :style="image.objectPosition ? { objectPosition: image.objectPosition } : undefined"
        >
        <RCImageHotspot v-if="editable" :image-url="image.image" :alt="image.alt" :object-position="image.objectPosition"
          :block-key="blockKey ?? ''" :image-index="index" full-tile-trigger :can-move-up="index > 0"
          :can-move-down="index < element.json_content.length - 1" :can-delete="element.json_content.length > 1" @update:image="replaceImage(index, $event)"
          @update:alt="updateImage(index, { alt: $event })" @update:object-position="updateImage(index, { objectPosition: $event })"
          @delete="removeImage(index)" @move-up="moveImage(index, index - 1)" @move-down="moveImage(index, index + 1)">
          <template #options>
            <Field class="border-t border-border pt-4">
              <FieldLabel>{{ $t('rich-content.image_width') }}</FieldLabel>
              <Select :model-value="image.colspan" @update:model-value="updateImage(index, { colspan: $event as ImageGrid['json_content'][number]['colspan'] })">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="col-span-full">
                    1/1
                  </SelectItem><SelectItem value="col-span-2">
                    1/3
                  </SelectItem>
                  <SelectItem value="col-span-3">
                    1/2
                  </SelectItem><SelectItem value="col-span-4">
                    2/3
                  </SelectItem>
                </SelectContent>
              </Select>
            </Field>
          </template>
        </RCImageHotspot>
      </div>
    </div>
    <RCAddPlaceholder
      v-if="editable && element.json_content.length"
      :label="$t('rich-content.add_image')"
      class="bottom-0 right-0 translate-x-full translate-y-1/2"
      @click="addEmptyImage"
    />
  </div>
</template>

<script setup lang="ts">
import { defineAsyncComponent, inject, nextTick } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';

import type { ImageGrid } from '@/Types/contentParts';

// Lazy-loaded: only ever mounted while `editable` — a static import would bundle the
// hotspot's image picker/focal-point UI (and its width-select field) into every public
// page that renders an image grid, which never reaches this branch at all.
const RCAddPlaceholder = defineAsyncComponent(() => import('../Editor/Fullscreen/RCAddPlaceholder.vue'));
const RCImageHotspot = defineAsyncComponent(() => import('../Editor/Fullscreen/RCImageHotspot.vue'));
const Field = defineAsyncComponent(() => import('@/Components/ui/field').then(m => m.Field));
const FieldLabel = defineAsyncComponent(() => import('@/Components/ui/field').then(m => m.FieldLabel));
const Select = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.Select));
const SelectContent = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectContent));
const SelectItem = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectItem));
const SelectTrigger = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectTrigger));
const SelectValue = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectValue));

const props = defineProps<{ element: ImageGrid; editable?: boolean; blockKey?: string }>();
const emit = defineEmits<(e: 'update:element', value: ImageGrid) => void>();
const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);

const getClassesForImage = (colspan: string): string => {
  if (colspan === 'col-span-full') {
    return `h-48 md:h-60 ${colspan}`;
  }
  return `md:h-40 ${colspan}`;
};

function updateImage(index: number, patch: Partial<ImageGrid['json_content'][number]>): void {
  const json_content = [...props.element.json_content];
  const image = json_content[index];
  if (!image) return;
  json_content[index] = { ...image, ...patch };
  emit('update:element', { ...props.element, json_content });
}

function replaceImage(index: number, image: { src: string; alt: string; title: string }): void {
  updateImage(index, { image: image.src, alt: image.alt, title: image.title });
}

function removeImage(index: number): void {
  if (props.element.json_content.length <= 1) return;
  emit('update:element', { ...props.element, json_content: props.element.json_content.filter((_, currentIndex) => currentIndex !== index) });
}

function moveImage(from: number, to: number): void {
  const json_content = [...props.element.json_content];
  const [image] = json_content.splice(from, 1);
  if (!image) return;
  json_content.splice(to, 0, image);
  emit('update:element', { ...props.element, json_content });
}

async function addEmptyImage(): Promise<void> {
  emit('update:element', { ...props.element, json_content: [...props.element.json_content, { colspan: 'col-span-2', image: '', alt: '' }] });
  await nextTick();
  hotspots?.openPopover(`${props.blockKey ?? ''}:image-${props.element.json_content.length}`);
}
</script>
