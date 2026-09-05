<template>
  <div :class="['relative', slideHeightClass]">
    <!-- Grayscale background photograph -->
    <img
      v-if="slide.imageSrc"
      :src="slide.imageSrc"
      :alt="slide.imageAlt"
      :style="slide.objectPosition ? { objectPosition: slide.objectPosition } : undefined"
      :class="['absolute inset-0 size-full object-cover object-center grayscale', SCRIM_IMAGE_OPACITY[scrimStrength]]"
      :loading="isFirstSlide ? 'eager' : 'lazy'"
      :fetchpriority="isFirstSlide ? 'high' : undefined"
      draggable="false"
    >

    <!-- Empty image placeholder in edit mode -->
    <div
      v-else-if="editable"
      class="absolute inset-0 z-10 flex flex-col items-center justify-center p-6 text-center"
    >
      <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-white/25 bg-ink/50 p-6 text-white/70 backdrop-blur-sm">
        <IFluentImage24Regular class="size-8 text-white/50" />
        <span class="text-sm font-medium">{{ $t('rich-content.slide_image') }}</span>
        <Button
          type="button"
          variant="outline"
          size="sm"
          class="mt-1 border-white/20 bg-ink/60 text-white backdrop-blur hover:bg-ink hover:text-white"
          data-rc-interactive
          @click="hotspots?.openPopover(imageHotspotId)"
        >
          <IFluentImage24Regular class="mr-1.5 size-4" />
          {{ $t('rich-content.select_image') }}
        </Button>
      </div>
    </div>

    <!-- Dual scrims -->
    <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/80 to-ink/25" />
    <div class="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-ink/40" />

    <!-- Slide actions / image hotspot when editable -->
    <div v-if="editable" class="absolute top-4 right-4 z-20 flex items-center gap-2">
      <Button
        v-if="canDeleteSlide"
        variant="ghost"
        size="icon"
        class="size-8 rounded-full border border-white/20 bg-ink/60 text-white backdrop-blur hover:border-red-500 hover:bg-red-500/20 hover:text-red-400"
        data-rc-interactive
        :aria-label="$t('rich-content.remove_slide')"
        :title="$t('rich-content.remove_slide')"
        @click="$emit('delete-slide')"
      >
        <IFluentDelete24Regular class="size-4" />
      </Button>

      <HeroCarouselImageHotspot
        :slide
        :slide-index
        :block-key="blockKey ?? ''"
        @update:slide="$emit('update:slide', $event)"
      />
    </div>

    <!-- Overlaid text block -->
    <div :class="slideContainerClass(slideAlign)">
      <div :class="slideCopyClass(slideAlign)">
        <EyebrowLabel v-if="slide.eyebrow || editable" class="text-brand-fill">
          <RCInlineText
            as="span"
            :model-value="slide.eyebrow ?? ''"
            :editable
            :placeholder="$t('rich-content.eyebrow')"
            @update:model-value="patchSlide({ eyebrow: $event })"
          />
        </EyebrowLabel>

        <!-- Title -->
        <h2
          v-if="!editable"
          class="rc-hero-title u-display mt-4 text-pretty text-[2.25rem] text-white sm:text-6xl lg:text-7xl"
        >
          <span v-if="hasTitle" v-html="slide.title" />
          <span v-else>{{ slide.title }}</span>
        </h2>
        <div
          v-else-if="!isTitleLive"
          class="rc-hero-title u-display mt-4 text-pretty text-[2.25rem] text-white sm:text-6xl lg:text-7xl"
          role="heading"
          aria-level="2"
        >
          <button
            type="button"
            class="block w-full cursor-text text-inherit"
            data-rc-interactive
            @click="claimTitle"
          >
            <span v-if="hasTitle" v-html="slide.title" />
            <span v-else class="text-white/40">{{ $t('rich-content.title') }}</span>
          </button>
        </div>
        <div v-else class="rc-hero-title u-display mt-4 text-pretty text-[2.25rem] text-white sm:text-6xl lg:text-7xl" data-rc-interactive @focusout="releaseTitle">
          <TiptapEditor
            :model-value="slide.title"
            preset="marks"
            toolbar="bubble"
            :show-bold="false"
            html
            class="rc-hero-title-editor u-display"
            :placeholder="$t('rich-content.title')"
            @update:model-value="patchSlide({ title: $event })"
          />
        </div>

        <!-- Subtitle -->
        <p v-if="slide.subtitle || editable" class="mt-5 max-w-xl text-pretty leading-relaxed text-white/85">
          <RCInlineText
            as="span"
            :model-value="slide.subtitle ?? ''"
            :editable
            :placeholder="$t('rich-content.subtitle')"
            @update:model-value="patchSlide({ subtitle: $event })"
          />
        </p>

        <!-- Description -->
        <div v-if="hasDesc || editable" class="mt-3 max-w-xl">
          <div v-if="!isDescriptionLive" class="rc-prose rc-prose-invert text-sm leading-relaxed text-white/85 sm:text-base">
            <button v-if="editable" type="button" class="block w-full cursor-text text-left text-inherit" data-rc-interactive @click="claimDescription">
              <RichContentTiptapHTML v-if="hasTiptapContent(slide.description)" :json_content="slide.description" />
              <span v-else-if="hasHtmlContent(slide.description)" v-html="slide.description" />
              <span v-else class="text-white/40">{{ $t('rich-content.description') }}</span>
            </button>
            <template v-else>
              <RichContentTiptapHTML v-if="hasTiptapContent(slide.description)" :json_content="slide.description" />
              <span v-else-if="hasHtmlContent(slide.description)" v-html="slide.description" />
            </template>
          </div>
          <div v-else class="rc-prose rc-prose-invert text-sm leading-relaxed sm:text-base" data-rc-interactive @focusout="releaseDescription">
            <TiptapEditor
              :model-value="slide.description"
              preset="marks"
              toolbar="bubble"
              class="rc-hero-description-editor"
              :placeholder="$t('rich-content.description')"
              @update:model-value="patchSlide({ description: $event })"
            />
          </div>
        </div>

        <!-- Call to action buttons -->
        <HeroButtons v-if="!editable" :buttons="slide.buttons" class="mt-7" />
        <HeroButtonsEditable
          v-else
          :buttons="slide.buttons"
          :block-key="`${blockKey ?? ''}:slide-${slideIndex}`"
          class="mt-7"
          @update:buttons="patchSlide({ buttons: $event })"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, inject } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import HeroButtons from '../RCHeroSection/HeroButtons.vue';
import HeroButtonsEditable from '../RCHeroSection/HeroButtonsEditable.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';

import HeroCarouselImageHotspot from './HeroCarouselImageHotspot.vue';

import { Button } from '@/Components/ui/button';
import { EyebrowLabel } from '@/Components/Public/Base';
import type { HeroCarousel } from '@/Types/contentParts';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

type Slide = HeroCarousel['json_content'][number];

const props = defineProps<{
  slide: Slide;
  slideIndex: number;
  slideHeightClass: string;
  scrimStrength: 'light' | 'medium' | 'dark';
  isFirstSlide?: boolean;
  editable?: boolean;
  blockKey?: string;
  canDeleteSlide?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:slide', value: Slide): void;
  (e: 'delete-slide'): void;
}>();

const SCRIM_IMAGE_OPACITY = {
  light: 'opacity-85',
  medium: 'opacity-70',
  dark: 'opacity-55',
} as const;

const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);

const slideAlign = computed(() => props.slide.align ?? 'start');

const titleHotspotId = computed(() => `${props.blockKey ?? ''}:slide-${props.slideIndex}:title`);
const descriptionHotspotId = computed(() => `${props.blockKey ?? ''}:slide-${props.slideIndex}:description`);
const imageHotspotId = computed(() => `${props.blockKey ?? ''}:slide-${props.slideIndex}:image`);

const hasTitle = computed(() => Boolean(props.slide.title?.replace(/<[^>]*>/g, '').trim()));
const hasDesc = computed(() => hasTiptapContent(props.slide.description) || hasHtmlContent(props.slide.description));

const isTitleLive = computed(() => !!props.editable && !!hotspots?.isTextFieldLive(titleHotspotId.value));
const isDescriptionLive = computed(() => !!props.editable && !!hotspots?.isTextFieldLive(descriptionHotspotId.value));

function slideContainerClass(align: 'start' | 'center' | 'end'): string {
  const base = 'relative z-10 mx-auto flex min-h-[inherit] max-w-7xl flex-col px-5 pt-24 sm:px-6 lg:px-8';
  const map = {
    start: 'justify-end items-start pb-20 text-left sm:pb-24',
    center: 'items-center justify-center pb-16 text-center',
    end: 'justify-end items-end pb-20 text-right sm:pb-24',
  } as const;
  return [base, map[align]].join(' ');
}

function slideCopyClass(align: 'start' | 'center' | 'end'): string {
  const map = {
    start: 'border-l-2 border-brand-fill pl-5 sm:pl-7',
    center: 'mx-auto',
    end: 'border-r-2 border-brand-fill pr-5 sm:pr-7',
  } as const;
  return ['max-w-2xl', map[align]].join(' ');
}

function hasTiptapContent(description: unknown): boolean {
  if (!description || typeof description !== 'object' || !('content' in description)) {
    return false;
  }
  const { content } = description as { content: unknown };
  return Array.isArray(content) && content.length > 0;
}

function hasHtmlContent(description: unknown): boolean {
  return typeof description === 'string' && Boolean(description.replace(/<[^>]*>/g, '').trim());
}

function patchSlide(patch: Partial<Slide>): void {
  emit('update:slide', { ...props.slide, ...patch });
}

function claimTitle(): void {
  if (props.editable) hotspots?.openTextField(titleHotspotId.value);
}

function releaseTitle(): void {
  hotspots?.close(titleHotspotId.value);
}

function claimDescription(): void {
  if (props.editable) hotspots?.openTextField(descriptionHotspotId.value);
}

function releaseDescription(): void {
  hotspots?.close(descriptionHotspotId.value);
}
</script>

<style scoped>
.rc-hero-title-editor :deep(.tiptap-content) {
  min-height: 0;
  overflow: visible;
  border: 0;
  background: transparent;
}

.rc-hero-title-editor :deep(.ProseMirror) {
  min-height: 0;
  padding: 0;
  font: inherit;
  text-transform: inherit;
  letter-spacing: inherit;
  line-height: inherit;
  color: white;
}

.rc-hero-title-editor :deep(.ProseMirror p) {
  margin: 0;
}

.rc-hero-title > button {
  font: inherit;
  text-align: inherit;
  text-transform: inherit;
  letter-spacing: inherit;
  line-height: inherit;
}

.rc-hero-title :deep(p) {
  margin: 0;
  font: inherit;
  color: inherit;
  text-align: inherit;
  letter-spacing: inherit;
  line-height: inherit;
}

.rc-hero-description-editor :deep(.tiptap-content),
.rc-hero-description-editor :deep(.ProseMirror) {
  min-height: 0;
  padding: 0;
  border: 0;
  background: transparent;
  font: inherit;
  color: rgba(255, 255, 255, 0.85);
  line-height: inherit;
}

.rc-hero-description-editor :deep(.ProseMirror p) {
  margin: 0;
}
</style>
