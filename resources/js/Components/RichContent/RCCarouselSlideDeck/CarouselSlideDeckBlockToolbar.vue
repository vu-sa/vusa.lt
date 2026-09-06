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
      <!-- Slide management -->
      <div class="flex items-center justify-between border-b border-border pb-2.5">
        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ $t('rich-content.slides') }} ({{ slides.length }})
        </span>
        <Button variant="outline" size="sm" data-rc-toolbar-add-slide @click="addSlide">
          <IFluentAdd12Regular class="mr-1 size-3.5" />
          {{ $t('rich-content.add_slide') }}
        </Button>
      </div>

      <!-- Slide items list -->
      <div v-if="slides.length > 0" class="flex flex-col gap-1.5 max-h-48 overflow-y-auto pr-0.5">
        <div
          v-for="(slide, index) in slides"
          :key="index"
          class="flex items-center justify-between gap-2 rounded-md border border-border bg-muted/40 p-1.5 text-xs"
        >
          <div class="flex items-center gap-2 min-w-0 flex-1">
            <button
              type="button"
              class="relative size-8 shrink-0 overflow-hidden rounded border border-border bg-muted flex items-center justify-center hover:opacity-80 transition-opacity"
              :title="$t('rich-content.select_image')"
              @click="activeSlideIndexForImage = index"
            >
              <img
                v-if="slide.imageSrc"
                :src="slide.imageSrc"
                :alt="slide.imageAlt || ''"
                class="size-full object-cover"
              >
              <IFluentImage24Regular v-else class="size-4 text-muted-foreground" />
            </button>
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium text-foreground">
                {{ slide.title || `${$t('rich-content.slide')} ${index + 1}` }}
              </p>
              <p v-if="slide.badge" class="truncate text-xs text-muted-foreground">
                {{ slide.badge }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1 shrink-0">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :disabled="index === 0"
              :title="$t('rich-content.move_up')"
              @click="moveSlide(index, index - 1)"
            >
              <IFluentArrowUp24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :disabled="index === slides.length - 1"
              :title="$t('rich-content.move_down')"
              @click="moveSlide(index, index + 1)"
            >
              <IFluentArrowDown24Regular class="size-3.5" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-7"
              :title="$t('rich-content.select_image')"
              @click="activeSlideIndexForImage = index"
            >
              <IFluentImage24Regular class="size-3.5" />
            </Button>
            <Button
              v-if="slides.length > 1"
              type="button"
              variant="ghost"
              size="icon"
              class="size-7 text-muted-foreground hover:text-destructive"
              :title="$t('rich-content.remove_slide')"
              @click="removeSlide(index)"
            >
              <IFluentDelete24Regular class="size-3.5" />
            </Button>
          </div>
        </div>
      </div>

      <!-- Carousel Options -->
      <div class="space-y-2 pt-1 border-t border-border">
        <div class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.show_navigation') }}</span>
          <Switch :model-value="asBoolean(carouselOptions.showNavigation ?? true)" @update:model-value="setOption('showNavigation', $event)" />
        </div>
        <div class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.show_thumbnails') }}</span>
          <Switch :model-value="asBoolean(carouselOptions.showThumbnails ?? true)" @update:model-value="setOption('showThumbnails', $event)" />
        </div>
        <div class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.enable_autoplay') }}</span>
          <Switch :model-value="asBoolean(carouselOptions.autoplay)" @update:model-value="setOption('autoplay', $event)" />
        </div>
        <div v-if="asBoolean(carouselOptions.autoplay)" class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.autoplay_delay') }}</span>
          <Input
            :model-value="carouselOptions.autoplayDelay ?? 8000"
            type="number"
            min="2000"
            max="30000"
            step="1000"
            class="h-7 w-20 text-xs"
            @update:model-value="setOption('autoplayDelay', Number($event))"
          />
        </div>
      </div>

      <!-- Width picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between border-t border-border pt-3">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <!-- Section Options -->
      <RCSectionToolbarOptions v-model="sectionOptions" :presentation-disabled />
    </div>

    <ImageSelector
      :show-modal="activeSlideIndexForImage !== null"
      selection-type="image"
      @update:show-modal="(open) => { if (!open) activeSlideIndexForImage = null; }"
      @submit="onSlideImageSubmit"
    />
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { asBoolean } from '../booleanish';
import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCSectionToolbarOptions from '../Editor/RCSectionToolbarOptions.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';

import ImageSelector from '@/Components/TipTap/ImageSelector.vue';
import { Button } from '@/Components/ui/button';
import { FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import type { CarouselSlideDeck, SectionOptions } from '@/Types/contentParts';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

type Slide = CarouselSlideDeck['json_content'][number];

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
  presentationDisabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const slides = computed<Slide[]>(() => (Array.isArray(props.content.json_content) ? props.content.json_content : []));
const carouselOptions = computed<CarouselSlideDeck['options']>(() => (props.content.options ?? {}) as CarouselSlideDeck['options']);
const activeSlideIndexForImage = ref<number | null>(null);

const contentType = computed(() => getContentType('carousel-slide-deck'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (carouselOptions.value?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

const sectionOptions = computed<SectionOptions>({
  get: () => (props.content.options ?? {}) as SectionOptions,
  set: val => emit('update:content', { ...props.content, options: val }),
});

function setOption(key: keyof CarouselSlideDeck['options'], value: unknown): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...carouselOptions.value,
      [key]: value,
    },
  });
}

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function onSlideImageSubmit(img: { src: string; alt: string }): void {
  if (activeSlideIndexForImage.value === null) return;
  const newSlides = [...slides.value];
  newSlides[activeSlideIndexForImage.value] = {
    ...newSlides[activeSlideIndexForImage.value],
    imageSrc: img.src,
    imageAlt: img.alt,
  };
  emit('update:content', {
    ...props.content,
    json_content: newSlides,
  });
  activeSlideIndexForImage.value = null;
}

function removeSlide(index: number): void {
  if (slides.value.length <= 1) return;
  const newSlides = slides.value.filter((_, i) => i !== index);
  emit('update:content', {
    ...props.content,
    json_content: newSlides,
  });
}

function moveSlide(from: number, to: number): void {
  const newSlides = [...slides.value];
  const [slide] = newSlides.splice(from, 1);
  if (!slide) return;
  newSlides.splice(to, 0, slide);
  emit('update:content', {
    ...props.content,
    json_content: newSlides,
  });
}

function addSlide(): void {
  const newSlide: Slide = {
    icon: 'info',
    badge: '',
    title: '',
    description: '',
    imageSrc: '',
    imageAlt: '',
    imageLeft: false,
    decorations: [],
  };

  emit('update:content', {
    ...props.content,
    json_content: [...slides.value, newSlide],
  });
}
</script>
