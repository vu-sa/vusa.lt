<template>
  <Popover :open="hotspots.isPopoverOpen(hotspotId)" @update:open="onOpenChange">
    <PopoverAnchor :reference="spotlightRef ?? undefined" />
    <button ref="spotlightRef" type="button" :class="[
      triggerClass,
      'transition-transform focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vusa-red focus-visible:ring-offset-2',
    ]"
      data-rc-interactive :aria-label="$t('rich-content.edit_image')" :title="$t('rich-content.edit_image')"
      @click.stop="hotspots.openPopover(hotspotId)"
      @keydown.stop>
      <span :class="imageUrl ? 'sr-only' : undefined">
        {{ imageUrl ? $t('rich-content.edit_image') : $t('rich-content.select_image') }}
      </span>
    </button>
    <PopoverContent v-if="hotspots.isPopoverOpen(hotspotId)" data-surface="public"
      class="max-h-[calc(100dvh-2rem)] w-[min(32rem,calc(100vw-2rem))] overflow-y-auto"
      @close-auto-focus.prevent @pointer-down-outside="(event) => { if (isImageModalOpen) event.preventDefault(); }"
      @interact-outside="(event) => { if (isImageModalOpen) event.preventDefault(); }">
      <div class="flex flex-col gap-5">
        <Field>
          <FieldLabel>{{ $t('rich-content.image') }}</FieldLabel>
          <div v-if="imageUrl" class="flex items-center gap-3">
            <img :src="imageUrl" alt="" class="aspect-video h-16 rounded-lg object-cover">
            <Button type="button" variant="outline" size="sm" @click="isImageModalOpen = true">
              {{ $t('rich-content.select_image') }}
            </Button>
            <Button type="button" variant="destructive" size="sm" @click="$emit('delete')">
              {{ $t('rich-content.delete_image') }}
            </Button>
          </div>
          <div v-else class="flex items-center gap-3">
            <Button type="button" variant="outline" size="sm" @click="isImageModalOpen = true">
              {{ $t('rich-content.select_image') }}
            </Button>
            <Button type="button" variant="destructive" size="sm" @click="$emit('delete')">
              {{ $t('rich-content.delete_image') }}
            </Button>
          </div>
        </Field>
        <template v-if="imageUrl">
          <Field class="border-t border-border pt-4">
            <FieldLabel>{{ $t('rich-content.image_alt_text') }}</FieldLabel>
            <Input :model-value="alt" type="text" :placeholder="$t('rich-content.enter_image_alt_text')"
              @update:model-value="$emit('update:alt', $event as string)" />
          </Field>
          <div class="border-t border-border pt-4">
            <FocalPointPicker :image-url :model-value="objectPosition ?? null"
              @update:model-value="$emit('update:object-position', $event as string)" />
          </div>
        </template>
        <div v-if="canMoveUp || canMoveDown" class="flex items-center gap-2 border-t border-border pt-4">
          <Button type="button" variant="outline" size="sm" :disabled="!canMoveUp" @click="move(-1)">
            <IFluentArrowUp24Regular class="mr-1 size-4" />
            {{ $t('rich-content.move_up') }}
          </Button>
          <Button type="button" variant="outline" size="sm" :disabled="!canMoveDown" @click="move(1)">
            <IFluentArrowDown24Regular class="mr-1 size-4" />
            {{ $t('rich-content.move_down') }}
          </Button>
        </div>
        <slot name="options" />
      </div>
    </PopoverContent>
  </Popover>
  <ImageSelector v-model:show-modal="isImageModalOpen" selection-type="image" @submit="onImageSubmit" />
</template>

<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { injectActiveHotspot } from './useActiveHotspot';

import ImageSelector from '@/Components/TipTap/ImageSelector.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';

const props = defineProps<{
  imageUrl: string;
  alt?: string;
  objectPosition?: string;
  blockKey: string;
  imageIndex: number;
  fullTileTrigger?: boolean;
  canMoveUp?: boolean;
  canMoveDown?: boolean;
}>();
const emit = defineEmits<{
  (e: 'update:image', image: { src: string; alt: string; title: string }): void;
  (e: 'update:alt', value: string): void;
  (e: 'update:object-position', value: string): void;
  (e: 'delete'): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
}>();

const hotspots = injectActiveHotspot();
const hotspotId = computed(() => `${props.blockKey}:image-${props.imageIndex}`);
const triggerClass = computed(() => {
  if (!props.imageUrl) {
    return 'absolute inset-0 z-20 flex items-center justify-center border-2 border-dashed border-border bg-background/80 text-sm font-medium text-muted-foreground hover:bg-muted/80';
  }

  return props.fullTileTrigger
    ? 'absolute inset-0 z-20 cursor-pointer'
    : 'absolute right-2 top-2 z-20 size-3 rounded-full bg-vusa-red shadow-[0_0_0_4px_rgb(var(--vusa-red)/0.2)] animate-pulse hover:scale-125 focus-visible:scale-125';
});
const spotlightRef = ref<HTMLElement | null>(null);
const isImageModalOpen = ref(false);

function onOpenChange(open: boolean): void {
  if (isImageModalOpen.value) return;
  if (open) hotspots.openPopover(hotspotId.value);
  else hotspots.close(hotspotId.value);
}

function onImageSubmit(image: { src: string; alt: string; title: string }): void {
  emit('update:image', image);
  isImageModalOpen.value = false;
}

async function move(direction: -1 | 1): Promise<void> {
  emit(direction === -1 ? 'move-up' : 'move-down');
  await nextTick();
  hotspots.openPopover(`${props.blockKey}:image-${props.imageIndex + direction}`);
}
</script>
