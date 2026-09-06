<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined"
    :title="element.options?.title"
    :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow"
    :band
    :align="element.options?.align ?? 'center'"
    :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator"
    inner="wide"
    :editable
    @update:header="updateOptions"
  >
    <div v-if="slides.length > 0" class="w-full">
      <Carousel
        ref="carouselRef"
        class="w-full max-w-5xl mx-auto"
        :opts="{
          align: 'start',
          loop: true,
        }"
        @init-api="(val) => carouselApi = val"
      >
        <CarouselContent>
          <CarouselItem v-for="(slide, index) in slides" :key="index">
            <div class="p-4">
              <div class="relative grid lg:grid-cols-2 gap-8 md:gap-12 items-center bg-card rounded-2xl p-8 md:p-12 border border-border">
                <!-- Delete slide button when editable -->
                <div v-if="editable && slides.length > 1" class="absolute right-4 top-4 z-10 flex items-center gap-2">
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-7 text-muted-foreground hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                    :title="$t('rich-content.remove_slide')"
                    data-rc-interactive
                    data-rc-carousel-remove-slide
                    @click.stop="removeSlide(index)"
                  >
                    <IFluentDelete24Regular class="size-3.5" />
                  </Button>
                </div>

                <div :class="['space-y-4 md:space-y-6', slide.imageLeft ? 'order-1 lg:order-2' : 'order-2 lg:order-1']">
                  <!-- Badge -->
                  <div class="inline-flex items-center gap-2 px-3 py-1 border border-border text-sm text-muted-foreground">
                    <button
                      v-if="editable"
                      type="button"
                      class="flex items-center justify-center hover:opacity-80 transition-opacity"
                      :title="$t('rich-content.icon')"
                      data-rc-interactive
                      @click.stop="activeSlideIndexForIcon = index"
                    >
                      <RCIcon :name="slide.icon" class="w-4 h-4 text-brand" />
                    </button>
                    <RCIcon v-else :name="slide.icon" class="w-4 h-4" />

                    <RCInlineText
                      v-if="editable"
                      as="span"
                      :model-value="slide.badge ?? ''"
                      :editable
                      :placeholder="$t('rich-content.enter_badge')"
                      @click.stop
                      @update:model-value="updateSlide(index, { ...slide, badge: $event })"
                    />
                    <template v-else>
                      {{ slide.badge }}
                    </template>
                  </div>

                  <!-- Title -->
                  <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-foreground leading-tight">
                    <RCInlineText
                      v-if="editable"
                      as="span"
                      :model-value="slide.title ?? ''"
                      :editable
                      :placeholder="$t('rich-content.enter_title')"
                      @click.stop
                      @update:model-value="updateSlide(index, { ...slide, title: $event })"
                    />
                    <template v-else>
                      {{ slide.title }}
                    </template>
                  </h3>

                  <!-- Description -->
                  <div class="rc-prose text-sm sm:text-base md:text-lg text-muted-foreground leading-relaxed">
                    <div v-if="!isDescriptionLive(index)">
                      <button
                        v-if="editable"
                        type="button"
                        class="block w-full cursor-text text-left text-inherit"
                        data-rc-interactive
                        @click.stop="claimDescription(index)"
                      >
                        <RichContentTiptapHTML v-if="hasTiptapContent(slide.description)" :json_content="slide.description" />
                        <span v-else-if="hasHtmlContent(slide.description)" v-html="slide.description" />
                        <span v-else class="text-muted-foreground/50">{{ $t('rich-content.description') }}</span>
                      </button>
                      <template v-else>
                        <RichContentTiptapHTML v-if="hasTiptapContent(slide.description)" :json_content="slide.description" />
                        <span v-else-if="hasHtmlContent(slide.description)" v-html="slide.description" />
                      </template>
                    </div>
                    <div v-else data-rc-interactive @focusout="releaseDescription(index)">
                      <TiptapEditor
                        :model-value="slide.description"
                        preset="marks"
                        toolbar="bubble"
                        class="rc-slide-description-editor"
                        :placeholder="$t('rich-content.description')"
                        @update:model-value="updateSlide(index, { ...slide, description: $event })"
                      />
                    </div>
                  </div>
                </div>

                <div :class="['relative', slide.imageLeft ? 'order-2 lg:order-1' : 'order-1 lg:order-2']">
                  <ImageWithDecorations
                    v-if="slide.imageSrc"
                    :src="slide.imageSrc"
                    :alt="slide.imageAlt"
                    height-class="h-64 md:h-80"
                    :decorations="slide.decorations"
                    :object-position="slide.objectPosition"
                    loading="lazy"
                  />
                  <!-- Empty image placeholder in edit mode -->
                  <div
                    v-else-if="editable"
                    class="flex h-64 md:h-80 flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/30 p-6 text-center"
                  >
                    <IFluentImage24Regular class="size-8 text-muted-foreground/60 mb-2" />
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      data-rc-interactive
                      @click.stop="activeSlideIndexForImage = index"
                    >
                      <IFluentImage24Regular class="mr-1.5 size-4" />
                      {{ $t('rich-content.select_image') }}
                    </Button>
                  </div>

                  <!-- Change / remove image overlay when image exists in edit mode -->
                  <div v-if="editable && slide.imageSrc" class="absolute bottom-3 right-3 z-10 flex items-center gap-1.5">
                    <Button
                      type="button"
                      variant="secondary"
                      size="icon-sm"
                      class="bg-background/80 backdrop-blur shadow-sm hover:bg-background"
                      :title="$t('rich-content.select_image')"
                      data-rc-interactive
                      @click.stop="activeSlideIndexForImage = index"
                    >
                      <IFluentImage24Regular class="size-3.5" />
                    </Button>
                    <Button
                      type="button"
                      variant="destructive"
                      size="icon-sm"
                      class="bg-background/80 backdrop-blur text-destructive shadow-sm hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40"
                      :title="$t('rich-content.delete_image')"
                      data-rc-interactive
                      @click.stop="updateSlide(index, { ...slide, imageSrc: '' })"
                    >
                      <IFluentDelete24Regular class="size-3.5" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </CarouselItem>
        </CarouselContent>

        <!-- Navigation buttons -->
        <CarouselPrevious
          v-if="asBoolean(element.options?.showNavigation)"
          class="hidden sm:flex -left-12 bg-background border-border hover:border-brand hover:text-brand text-foreground"
          @click="restartCarouselAutoplay"
        />
        <CarouselNext
          v-if="asBoolean(element.options?.showNavigation)"
          class="hidden sm:flex -right-12 bg-background border-border hover:border-brand hover:text-brand text-foreground"
          @click="restartCarouselAutoplay"
        />

        <!-- Photo Preview Navigation -->
        <div v-if="asBoolean(element.options?.showThumbnails)" class="flex flex-wrap justify-center mt-2 xl:mt-8 gap-3">
          <button
            v-for="(slide, index) in slides"
            :key="index"
            class="relative group transition-all duration-200"
            :class="{ '': currentSlide === index }"
            @click="() => { carouselApi?.scrollTo(index); restartCarouselAutoplay(); }"
          >
            <img
              v-if="slide.imageSrc"
              :src="slide.imageSrc"
              :alt="slide.imageAlt || ''"
              class="w-14 h-10 sm:w-16 sm:h-12 object-cover rounded-lg transition-all duration-200"
              :class="{
                'opacity-100 scale-105 blur-[1px]': currentSlide === index,
                'opacity-70 hover:opacity-90 scale-100 hover:scale-105': currentSlide !== index
              }"
              loading="lazy"
            >
            <div
              v-else
              class="w-14 h-10 sm:w-16 sm:h-12 flex items-center justify-center rounded-lg border border-border bg-muted/60 transition-all duration-200"
              :class="{
                'opacity-100 scale-105': currentSlide === index,
                'opacity-70 hover:opacity-90 scale-100 hover:scale-105': currentSlide !== index
              }"
            >
              <IFluentImage24Regular class="size-4 text-muted-foreground" />
            </div>

            <!-- Icon overlay for active slide -->
            <div
              v-if="currentSlide === index"
              class="absolute inset-0 bg-ink/30 rounded-lg flex items-center justify-center"
            >
              <RCIcon :name="slide.icon" class="w-3 h-3 sm:w-4 sm:h-4 text-white drop-shadow-sm" />
            </div>
            <!-- Category label -->
            <div
              class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs text-muted-foreground font-medium opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap"
            >
              {{ slide.badge }}
            </div>
          </button>
        </div>
      </Carousel>

      <div v-if="editable" class="mt-6 flex justify-center">
        <RCAddPlaceholder :label="$t('rich-content.add_slide')" @click="addSlide" />
      </div>
    </div>

    <!-- Zero slides empty state in edit mode -->
    <div
      v-else-if="editable"
      class="flex min-h-[250px] flex-col items-center justify-center rounded-2xl border border-dashed border-border p-8 text-center"
    >
      <p class="mb-4 text-sm text-muted-foreground">
        {{ $t('rich-content.no_slides') }}
      </p>
      <Button variant="outline" size="sm" @click="addSlide">
        <IFluentAdd12Regular class="mr-1 size-3.5" />
        {{ $t('rich-content.add_slide') }}
      </Button>
    </div>

    <!-- Image selector modal -->
    <ImageSelector
      :show-modal="activeSlideIndexForImage !== null"
      selection-type="image"
      @update:show-modal="(open) => { if (!open) activeSlideIndexForImage = null; }"
      @submit="onSlideImageSubmit"
    />

    <!-- Icon selection dialog -->
    <Dialog :open="activeSlideIndexForIcon !== null" @update:open="(open) => { if (!open) activeSlideIndexForIcon = null; }">
      <DialogContent class="max-w-sm">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.icon') }}</DialogTitle>
        </DialogHeader>
        <div v-if="activeSlideIndexForIcon !== null && slides[activeSlideIndexForIcon]" class="py-2">
          <RCIconSelect
            :model-value="slides[activeSlideIndexForIcon].icon"
            allow-none
            @update:model-value="updateSlideIcon"
          />
        </div>
      </DialogContent>
    </Dialog>
  </RCSection>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, inject, onMounted, onUnmounted, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCIcon from '../RCIcon.vue';
import RCIconSelect from '../RCIconSelect.vue';
import RCSection from '../RCSection.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import RCAddPlaceholder from '../Editor/Fullscreen/RCAddPlaceholder.vue';
import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';
import { asBoolean } from '../booleanish';
import type { BandResolution } from '../bandLayout';

import { Button } from '@/Components/ui/button';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import ImageSelector from '@/Components/TipTap/ImageSelector.vue';
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import type { CarouselSlideDeck } from '@/Types/contentParts';
import { hasHtmlText } from '@/Utils/String';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentImage24Regular from '~icons/fluent/image24-regular';

const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

type Slide = CarouselSlideDeck['json_content'][number];

const props = defineProps<{
  element: CarouselSlideDeck;
  html?: boolean;
  anchorId?: number | null;
  band?: BandResolution;
  editable?: boolean;
  blockKey?: string;
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: CarouselSlideDeck) => void>();

const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);

const slides = computed<Slide[]>(() => (Array.isArray(props.element.json_content) ? props.element.json_content : []));

const activeSlideIndexForImage = ref<number | null>(null);
const activeSlideIndexForIcon = ref<number | null>(null);

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}

function updateSlide(index: number, updatedSlide: Slide): void {
  const newSlides = [...slides.value];
  newSlides[index] = updatedSlide;
  emit('update:element', {
    ...props.element,
    json_content: newSlides,
  });
}

function removeSlide(index: number): void {
  if (slides.value.length <= 1) return;
  const newSlides = slides.value.filter((_, i) => i !== index);
  emit('update:element', {
    ...props.element,
    json_content: newSlides,
  });
  if (currentSlide.value >= newSlides.length) {
    currentSlide.value = Math.max(0, newSlides.length - 1);
  }
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
  emit('update:element', {
    ...props.element,
    json_content: [...slides.value, newSlide],
  });
}

function onSlideImageSubmit(img: { src: string; alt: string }): void {
  if (activeSlideIndexForImage.value === null) return;
  const slide = slides.value[activeSlideIndexForImage.value];
  if (!slide) return;
  updateSlide(activeSlideIndexForImage.value, {
    ...slide,
    imageSrc: img.src,
    imageAlt: img.alt,
  });
  activeSlideIndexForImage.value = null;
}

function updateSlideIcon(icon: string): void {
  if (activeSlideIndexForIcon.value === null) return;
  const slide = slides.value[activeSlideIndexForIcon.value];
  if (!slide) return;
  updateSlide(activeSlideIndexForIcon.value, {
    ...slide,
    icon,
  });
  activeSlideIndexForIcon.value = null;
}

function descHotspotId(index: number): string {
  return `${props.blockKey ?? ''}:slide-${index}:desc`;
}

function isDescriptionLive(index: number): boolean {
  return !!props.editable && !!hotspots?.isTextFieldLive(descHotspotId(index));
}

function claimDescription(index: number): void {
  if (props.editable) hotspots?.openTextField(descHotspotId(index));
}

function releaseDescription(index: number): void {
  hotspots?.close(descHotspotId(index));
}

function hasTiptapContent(description: unknown): boolean {
  if (!description || typeof description !== 'object' || !('content' in description)) {
    return false;
  }
  const { content } = description as { content: unknown };
  return Array.isArray(content) && content.length > 0;
}

function hasHtmlContent(description: unknown): boolean {
  return typeof description === 'string' && hasHtmlText(description);
}

// Carousel functionality
const carouselRef = ref();
const carouselApi = ref();
const currentSlide = ref(0);
let carouselAutoplayInterval: NodeJS.Timeout | null = null;

const startCarouselAutoplay = () => {
  if (props.editable || !carouselApi.value || carouselAutoplayInterval) return;

  carouselAutoplayInterval = setInterval(() => {
    carouselApi.value?.scrollNext();
  }, props.element.options?.autoplayDelay || 8000);
};

const stopCarouselAutoplay = () => {
  if (carouselAutoplayInterval) {
    clearInterval(carouselAutoplayInterval);
    carouselAutoplayInterval = null;
  }
};

const restartCarouselAutoplay = () => {
  stopCarouselAutoplay();
  if (props.editable) return;
  setTimeout(startCarouselAutoplay, props.element.options?.autoplayDelay || 8000);
};

watch(carouselApi, (api) => {
  if (!api) return;

  currentSlide.value = api.selectedScrollSnap();

  api.on('select', () => {
    currentSlide.value = api.selectedScrollSnap();
  });

  if (!props.editable && asBoolean(props.element.options?.autoplay)) {
    startCarouselAutoplay();
  }

  api.on('pointerDown', stopCarouselAutoplay);
  api.on('pointerUp', restartCarouselAutoplay);
});

watch(() => props.editable, (isEditable) => {
  if (isEditable) {
    stopCarouselAutoplay();
  }
  else if (asBoolean(props.element.options?.autoplay)) {
    startCarouselAutoplay();
  }
});

onMounted(() => {
  // Autoplay will start when carousel API is ready via watcher
});

onUnmounted(() => {
  stopCarouselAutoplay();
});
</script>

<style scoped>
.rc-slide-description-editor :deep(.tiptap-content),
.rc-slide-description-editor :deep(.ProseMirror) {
  min-height: 0;
  padding: 0;
  border: 0;
  background: transparent;
  font: inherit;
  line-height: inherit;
}

.rc-slide-description-editor :deep(.ProseMirror p) {
  margin: 0;
}
</style>
