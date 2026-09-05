<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
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
        <Button variant="outline" size="sm" @click="addSlide">
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
              <p v-if="slide.eyebrow" class="truncate text-[10px] text-muted-foreground">
                {{ slide.eyebrow }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-1 shrink-0">
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

      <!-- Carousel / Slide Options -->
      <div class="grid grid-cols-2 gap-2">
        <Field>
          <FieldLabel>{{ $t('rich-content.carousel_height') }}</FieldLabel>
          <Select :model-value="carouselOptions.height ?? 'md'" @update:model-value="setOption('height', $event)">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
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

        <Field>
          <FieldLabel>{{ $t('rich-content.scrim_strength') }}</FieldLabel>
          <Select :model-value="carouselOptions.scrim ?? 'medium'" @update:model-value="setOption('scrim', $event)">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="light">
                {{ $t('rich-content.scrim_light') }}
              </SelectItem>
              <SelectItem value="medium">
                {{ $t('rich-content.scrim_medium') }}
              </SelectItem>
              <SelectItem value="dark">
                {{ $t('rich-content.scrim_dark') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>
      </div>

      <!-- Multi-slide controls toggle -->
      <template v-if="slides.length > 1">
        <div class="flex items-center justify-between pt-1">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.show_arrows') }}</span>
          <Switch :model-value="asBoolean(carouselOptions.showArrows ?? true)" @update:model-value="setOption('showArrows', $event)" />
        </div>
        <div class="flex items-center justify-between">
          <span class="text-xs text-muted-foreground">{{ $t('rich-content.show_indicators') }}</span>
          <Switch :model-value="asBoolean(carouselOptions.showIndicators ?? true)" @update:model-value="setOption('showIndicators', $event)" />
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
      </template>
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
import type { ContentPart } from '../Types';

import ImageSelector from '@/Components/TipTap/ImageSelector.vue';
import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import type { HeroCarousel } from '@/Types/contentParts';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

type Slide = HeroCarousel['json_content'][number];

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const slides = computed<Slide[]>(() => (Array.isArray(props.content.json_content) ? props.content.json_content : []));
const carouselOptions = computed<HeroCarousel['options']>(() => (props.content.options ?? {}) as HeroCarousel['options']);
const activeSlideIndexForImage = ref<number | null>(null);

function setOption(key: keyof HeroCarousel['options'], value: unknown): void {
  emit('update:content', {
    ...props.content,
    options: {
      ...carouselOptions.value,
      [key]: value,
    },
  });
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

function addSlide(): void {
  const newSlide: Slide = {
    eyebrow: '',
    title: '',
    subtitle: '',
    description: '',
    imageSrc: '',
    imageAlt: '',
    align: 'start',
    buttons: [],
  };

  emit('update:content', {
    ...props.content,
    json_content: [...slides.value, newSlide],
  });
}
</script>
