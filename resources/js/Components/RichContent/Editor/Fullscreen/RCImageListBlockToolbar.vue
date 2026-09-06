<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @update:content="$emit('update:content', $event)"
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <div class="flex items-center justify-between border-b border-border pb-2.5">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('rich-content.images') }} ({{ images.length }})
        </span>
        <Button variant="outline" size="sm" @click="activeImageIndex = -1">
          <IFluentAdd12Regular class="mr-1 size-3.5" />
          {{ $t('rich-content.add_image') }}
        </Button>
      </div>

      <div v-if="images.length" class="flex max-h-48 flex-col gap-1.5 overflow-y-auto pr-0.5">
        <div v-for="(image, index) in images" :key="index"
          class="flex items-center justify-between gap-2 rounded-md border border-border bg-muted/40 p-1.5 text-xs">
          <button type="button"
            class="relative flex size-8 shrink-0 items-center justify-center overflow-hidden rounded border border-border bg-muted transition-opacity hover:opacity-80"
            :title="$t('rich-content.select_image')" @click="activeImageIndex = index">
            <img v-if="image.src" :src="image.src" :alt="image.alt" class="size-full object-cover">
            <IFluentImage24Regular v-else class="size-4 text-muted-foreground" />
          </button>
          <span class="min-w-0 flex-1 truncate font-medium text-foreground">
            {{ image.alt || image.title || `${$t('rich-content.image')} ${index + 1}` }}
          </span>
          <Button type="button" variant="ghost" size="icon" class="size-7"
            :disabled="index === 0" :title="$t('rich-content.move_up')" @click="moveImage(index, index - 1)">
            <IFluentArrowUp24Regular class="size-3.5" />
          </Button>
          <Button type="button" variant="ghost" size="icon" class="size-7"
            :disabled="index === images.length - 1" :title="$t('rich-content.move_down')" @click="moveImage(index, index + 1)">
            <IFluentArrowDown24Regular class="size-3.5" />
          </Button>
          <Button type="button" variant="ghost" size="icon" class="size-7 text-muted-foreground hover:text-destructive"
            :disabled="images.length <= 1"
            :title="$t('rich-content.delete_image')" @click="removeImage(index)">
            <IFluentDelete24Regular class="size-3.5" />
          </Button>
        </div>
      </div>

      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between border-t border-border pt-3">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker
          :model-value="currentWidth"
          :allowed-widths
          @update:model-value="setWidth"
        />
      </div>

      <template v-if="isPhotoGallery">
        <div class="grid grid-cols-2 gap-2 border-t border-border pt-3">
          <Field>
            <FieldLabel>{{ $t('rich-content.columns') }}</FieldLabel>
            <Select
              :model-value="galleryOptions.columns"
              @update:model-value="updateGalleryOptions({ columns: $event as PhotoGalleryGrid['options']['columns'] })"
            >
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="2">
                  2
                </SelectItem><SelectItem value="3">
                  3
                </SelectItem><SelectItem value="4">
                  4
                </SelectItem>
              </SelectContent>
            </Select>
          </Field>
          <Field>
            <FieldLabel>{{ $t('rich-content.gap_size') }}</FieldLabel>
            <Select
              :model-value="galleryOptions.gap"
              @update:model-value="updateGalleryOptions({ gap: $event as PhotoGalleryGrid['options']['gap'] })"
            >
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="small">
                  {{ $t('rich-content.small') }}
                </SelectItem><SelectItem value="medium">
                  {{ $t('rich-content.medium') }}
                </SelectItem><SelectItem value="large">
                  {{ $t('rich-content.large') }}
                </SelectItem>
              </SelectContent>
            </Select>
          </Field>
        </div>
        <div class="flex items-center justify-between">
          <FieldLabel>{{ $t('rich-content.enable_lightbox') }}</FieldLabel>
          <Switch
            :model-value="galleryOptions.showLightbox"
            @update:model-value="updateGalleryOptions({ showLightbox: $event })"
          />
        </div>
        <RCSectionToolbarOptions v-model="galleryOptions" />
      </template>
    </div>

    <ImageSelector
      :show-modal="activeImageIndex !== null"
      selection-type="image"
      @update:show-modal="(open) => { if (!open) activeImageIndex = null; }"
      @submit="onImageSubmit"
    />
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { getContentType, type BlockWidth, type ContentPart } from '../../Types';
import { withWidth } from '../blockWidth';
import RCSectionToolbarOptions from '../RCSectionToolbarOptions.vue';
import RCWidthPicker from '../RCWidthPicker.vue';

import RCBlockToolbarShell from './RCBlockToolbarShell.vue';

import ImageSelector from '@/Components/TipTap/ImageSelector.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import type { ImageGrid, PhotoGalleryGrid } from '@/Types/contentParts';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

type ImageListPart = ContentPart<ImageGrid['json_content'] | PhotoGalleryGrid['json_content']>;
interface PickerImage { src: string; alt: string; title: string }
interface ListedImage { src: string; alt: string; title?: string }

const props = defineProps<{
  content: ImageListPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ImageListPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const activeImageIndex = ref<number | null>(null);
const isImageGrid = computed(() => props.content.type === 'image-grid');
const isPhotoGallery = computed(() => props.content.type === 'photo-gallery');
const contentType = computed(() => getContentType(props.content.type));
const allowedWidths = computed<BlockWidth[]>(() => (
  contentType.value.allowedWidths ?? [contentType.value.defaultWidth]
));
const currentWidth = computed<BlockWidth>(() => (
  (props.content.options?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth
));
const galleryOptions = computed<PhotoGalleryGrid['options']>({
  get: () => (props.content.options ?? { columns: '4', gap: 'medium', showLightbox: true }) as PhotoGalleryGrid['options'],
  set: options => emit('update:content', { ...props.content, options }),
});
const images = computed<ListedImage[]>(() => props.content.json_content.map(image => (
  isImageGrid.value
    ? { src: (image as ImageGrid['json_content'][number]).image, alt: (image as ImageGrid['json_content'][number]).alt ?? '', title: (image as ImageGrid['json_content'][number]).title }
    : { src: (image as PhotoGalleryGrid['json_content'][number]).src, alt: (image as PhotoGalleryGrid['json_content'][number]).alt, title: (image as PhotoGalleryGrid['json_content'][number]).title }
)));

function onImageSubmit(image: PickerImage): void {
  const index = activeImageIndex.value;
  if (index === null) return;

  const next = [...props.content.json_content];
  if (isImageGrid.value) {
    const item: ImageGrid['json_content'][number] = { colspan: 'col-span-2', image: image.src, alt: image.alt, title: image.title };
    if (index === -1) next.push(item);
    else next[index] = { ...(next[index] as ImageGrid['json_content'][number]), ...item };
  }
  else {
    const item: PhotoGalleryGrid['json_content'][number] = { src: image.src, alt: image.alt, title: image.title, heightClass: 'h-52', decorations: [] };
    if (index === -1) next.push(item);
    else next[index] = { ...(next[index] as PhotoGalleryGrid['json_content'][number]), ...item };
  }

  emit('update:content', { ...props.content, json_content: next });
  activeImageIndex.value = null;
}

function removeImage(index: number): void {
  if (props.content.json_content.length <= 1) return;
  emit('update:content', { ...props.content, json_content: props.content.json_content.filter((_, currentIndex) => currentIndex !== index) });
}

function moveImage(from: number, to: number): void {
  const json_content = [...props.content.json_content];
  const [image] = json_content.splice(from, 1);
  if (!image) return;
  json_content.splice(to, 0, image);
  emit('update:content', { ...props.content, json_content });
}

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width) as ImageListPart);
}

function updateGalleryOptions(patch: Partial<PhotoGalleryGrid['options']>): void {
  emit('update:content', { ...props.content, options: { ...galleryOptions.value, ...patch } });
}
</script>
