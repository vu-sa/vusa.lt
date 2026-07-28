<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :background="element.options?.background ?? 'none'" :padding="element.options?.padding ?? 'md'"
    :rounded="element.options?.rounded ?? 'none'" :align="element.options?.align ?? 'center'"
    :heading-level="element.options?.headingLevel" :show-separator="element.options?.showSeparator"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
      <Carousel ref="carouselRef" class="w-full max-w-5xl mx-auto" :opts="{
        align: 'start',
        loop: true,
      }" @init-api="(val) => carouselApi = val">
        <CarouselContent>
          <CarouselItem v-for="(slide, index) in element.json_content" :key="index">
            <div class="p-4">
              <div class="grid lg:grid-cols-2 gap-8 md:gap-12 items-center bg-white dark:bg-zinc-800 rounded-2xl p-8 md:p-12 shadow-sm border border-zinc-100 dark:border-zinc-700">
                <div :class="['space-y-4 md:space-y-6', slide.imageLeft ? 'order-1 lg:order-2' : 'order-2 lg:order-1']">
                  <!-- Badge -->
                  <div class="inline-flex items-center gap-2 px-3 py-1 bg-zinc-100 dark:bg-zinc-700 rounded-full text-sm text-zinc-600 dark:text-zinc-400">
                    <RCIcon :name="slide.icon" class="w-4 h-4" />
                    {{ slide.badge }}
                  </div>

                  <!-- Title -->
                  <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
                    {{ slide.title }}
                  </h3>

                  <!-- Description (authored as Tiptap JSON; no server-rendered html for this type) -->
                  <div class="rc-prose text-sm sm:text-base md:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    <RichContentTiptapHTML :json_content="slide.description" />
                  </div>
                </div>

                <div :class="['relative', slide.imageLeft ? 'order-2 lg:order-1' : 'order-1 lg:order-2']">
                  <ImageWithDecorations
                    :src="slide.imageSrc"
                    :alt="slide.imageAlt"
                    height-class="h-64 md:h-80"
                    :decorations="slide.decorations"
                    :object-position="slide.objectPosition"
                    loading="lazy"
                  />
                </div>
              </div>
            </div>
          </CarouselItem>
        </CarouselContent>

        <!-- Navigation buttons -->
        <CarouselPrevious
          v-if="element.options?.showNavigation"
          class="hidden sm:flex -left-12 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-500 text-zinc-900 dark:text-zinc-100"
          @click="restartCarouselAutoplay" />
        <CarouselNext
          v-if="element.options?.showNavigation"
          class="hidden sm:flex -right-12 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-500 text-zinc-900 dark:text-zinc-100"
          @click="restartCarouselAutoplay" />

        <!-- Photo Preview Navigation -->
        <div v-if="element.options?.showThumbnails" class="flex flex-wrap justify-center mt-2 xl:mt-8 gap-3">
          <button
            v-for="(slide, index) in element.json_content"
            :key="index"
            class="relative group transition-all duration-200"
            :class="{ '': currentSlide === index }"
            @click="() => { carouselApi?.scrollTo(index); restartCarouselAutoplay(); }">
            <img
              :src="slide.imageSrc"
              :alt="slide.imageAlt"
              class="w-14 h-10 sm:w-16 sm:h-12 object-cover rounded-lg shadow-sm transition-all duration-200"
              :class="{
                'opacity-100 scale-105 blur-[1px]': currentSlide === index,
                'opacity-70 hover:opacity-90 scale-100 hover:scale-105': currentSlide !== index
              }"
              loading="lazy">
            <!-- Icon overlay for active slide -->
            <div v-if="currentSlide === index"
              class="absolute inset-0 bg-zinc-900/20 rounded-lg flex items-center justify-center">
              <RCIcon :name="slide.icon" class="w-3 h-3 sm:w-4 sm:h-4 text-white drop-shadow-sm" />
            </div>
            <!-- Category label -->
            <div
              class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs text-zinc-600 font-medium opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
              {{ slide.badge }}
            </div>
          </button>
        </div>
      </Carousel>
  </RCSection>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import RCIcon from '../RCIcon.vue';
import RCSection from '../RCSection.vue';
import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import type { CarouselSlideDeck } from '@/Types/contentParts';

const { element } = defineProps<{
  element: CarouselSlideDeck;
  html?: boolean;
  anchorId?: number | null;
}>();

// Carousel functionality
const carouselApi = ref();
const currentSlide = ref(0);
let carouselAutoplayInterval: NodeJS.Timeout | null = null;

// Carousel autoplay functionality
const startCarouselAutoplay = () => {
  if (!carouselApi.value || carouselAutoplayInterval) return;

  carouselAutoplayInterval = setInterval(() => {
    carouselApi.value?.scrollNext();
  }, element.options?.autoplayDelay || 8000);
};

const stopCarouselAutoplay = () => {
  if (carouselAutoplayInterval) {
    clearInterval(carouselAutoplayInterval);
    carouselAutoplayInterval = null;
  }
};

const restartCarouselAutoplay = () => {
  stopCarouselAutoplay();
  setTimeout(startCarouselAutoplay, element.options?.autoplayDelay || 8000);
};

// Watch for carousel API changes and set up slide tracking
watch(carouselApi, (api) => {
  if (!api) return;

  // Set initial slide
  currentSlide.value = api.selectedScrollSnap();

  // Listen for slide changes
  api.on('select', () => {
    currentSlide.value = api.selectedScrollSnap();
  });

  // Start autoplay if enabled
  if (element.options?.autoplay) {
    startCarouselAutoplay();
  }

  // Handle user interactions with carousel - pause and restart autoplay
  api.on('pointerDown', stopCarouselAutoplay);
  api.on('pointerUp', restartCarouselAutoplay);
});

// Lifecycle hooks for cleanup
onMounted(() => {
  // Autoplay will start when carousel API is ready via watcher
});

onUnmounted(() => {
  stopCarouselAutoplay();
});
</script>