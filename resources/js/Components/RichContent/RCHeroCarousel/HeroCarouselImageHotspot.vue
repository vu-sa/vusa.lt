<template>
  <div class="relative">
    <Popover :open="hotspots.isPopoverOpen(hotspotId)" @update:open="onOpenChange">
      <PopoverAnchor :reference="spotlightRef ?? undefined" />
      <button
        ref="spotlightRef"
        type="button"
        :class="[
          'size-3 shrink-0 rounded-full bg-vusa-red shadow-[0_0_0_4px_rgb(var(--vusa-red)/0.2)]',
          'animate-pulse transition-transform hover:scale-125 focus-visible:scale-125',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vusa-red focus-visible:ring-offset-2',
        ]"
        data-rc-interactive
        :aria-label="$t('rich-content.edit_image')"
        :title="$t('rich-content.edit_image')"
        @click="hotspots.openPopover(hotspotId)"
      >
        <span class="sr-only">{{ $t('rich-content.edit_image') }}</span>
      </button>

      <PopoverContent
        v-if="hotspots.isPopoverOpen(hotspotId)"
        data-surface="public"
        class="max-h-[calc(100dvh-2rem)] w-[min(32rem,calc(100vw-2rem))] overflow-y-auto"
        @close-auto-focus.prevent
        @pointer-down-outside="(e) => { if (isImageModalOpen) e.preventDefault(); }"
        @interact-outside="(e) => { if (isImageModalOpen) e.preventDefault(); }"
      >
        <div class="flex flex-col gap-5">
          <Field>
            <FieldLabel>{{ $t('rich-content.slide_image') }}</FieldLabel>
            <Button v-if="!slide.imageSrc" type="button" variant="outline" size="sm" @click="isImageModalOpen = true">
              <IFluentImage24Regular class="mr-1.5 size-4" />
              {{ $t('rich-content.select_image') }}
            </Button>
            <div v-else class="flex items-center gap-3">
              <img :src="slide.imageSrc" alt="" class="aspect-video h-16 rounded-lg object-cover">
              <Button type="button" variant="outline" size="sm" @click="isImageModalOpen = true">
                <IFluentImage24Regular class="mr-1.5 size-4" />
                {{ $t('rich-content.select_image') }}
              </Button>
              <Button type="button" variant="destructive" size="sm" @click="patchSlide({ imageSrc: '' })">
                {{ $t('rich-content.delete_image') }}
              </Button>
            </div>
          </Field>

          <template v-if="slide.imageSrc">
            <Field class="border-t border-border pt-4">
              <FieldLabel>{{ $t('rich-content.image_alt_text') }}</FieldLabel>
              <Input
                :model-value="slide.imageAlt ?? ''"
                type="text"
                :placeholder="$t('rich-content.enter_image_alt_text')"
                @update:model-value="patchSlide({ imageAlt: $event as string })"
              />
            </Field>

            <div class="border-t border-border pt-4">
              <FocalPointPicker
                :image-url="slide.imageSrc"
                :model-value="slide.objectPosition ?? null"
                @update:model-value="(val: string) => patchSlide({ objectPosition: val })"
              />
            </div>
          </template>

          <Field class="border-t border-border pt-4">
            <FieldLabel>{{ $t('rich-content.hero_carousel_text_position') }}</FieldLabel>
            <Select :model-value="slide.align ?? 'start'" @update:model-value="patchSlide({ align: $event as 'start' | 'center' | 'end' })">
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="start">
                  {{ $t('rich-content.hero_carousel_position_start') }}
                </SelectItem>
                <SelectItem value="center">
                  {{ $t('rich-content.hero_carousel_position_center') }}
                </SelectItem>
                <SelectItem value="end">
                  {{ $t('rich-content.hero_carousel_position_end') }}
                </SelectItem>
              </SelectContent>
            </Select>
          </Field>
        </div>
      </PopoverContent>
    </Popover>

    <ImageSelector
      v-model:show-modal="isImageModalOpen"
      selection-type="image"
      @submit="onImageSubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';

import ImageSelector from '@/Components/TipTap/ImageSelector.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';
import type { HeroCarousel } from '@/Types/contentParts';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

type Slide = HeroCarousel['json_content'][number];

const props = defineProps<{
  slide: Slide;
  slideIndex: number;
  blockKey: string;
}>();

const emit = defineEmits<(e: 'update:slide', value: Slide) => void>();

const hotspots = injectActiveHotspot();
const hotspotId = computed(() => `${props.blockKey}:slide-${props.slideIndex}:image`);
const spotlightRef = ref<HTMLElement | null>(null);
const isImageModalOpen = ref(false);

function patchSlide(patch: Partial<Slide>): void {
  emit('update:slide', { ...props.slide, ...patch });
}

function onImageSubmit(img: { src: string; alt: string }): void {
  patchSlide({ imageSrc: img.src, imageAlt: img.alt });
  isImageModalOpen.value = false;
}

function onOpenChange(open: boolean): void {
  if (isImageModalOpen.value) return;
  if (open) hotspots.openPopover(hotspotId.value);
  else hotspots.close(hotspotId.value);
}
</script>
