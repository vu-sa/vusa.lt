<template>
  <!-- Full-bleed section, inset rounded panel: the section spans the page, the photo
       panel floats inside it with page-gutter margins. That gutter is where the
       arrows live, and the dots sit below the panel — slide content never has
       navigation on top of it. No RCSection chrome: like HeroElement, the photo IS
       the background, so section title/padding options have nothing to sit on. -->
  <section
    :id="anchorId ? `rc-${anchorId}` : undefined"
    :class="['relative isolate scroll-mt-32 px-4 pt-4 pb-4 sm:px-6 sm:pt-6 sm:pb-5 lg:px-8', isFirstElement && '-mt-8 sm:-mt-6 md:-mt-4 2xl:mt-0']"
  >
    <!-- Hover/focus pause lands on the Carousel (its root is a role="region"
         tabindex="0" element, so these interactions have an accessible target);
         listeners fall through to it from here. -->
    <Carousel
      class="mx-auto w-full max-w-[110rem]"
      :opts="{ loop: hasMultipleSlides }"
      @mouseenter="stopCarouselAutoplay"
      @mouseleave="restartCarouselAutoplay"
      @focusin="stopCarouselAutoplay"
      @focusout="restartCarouselAutoplay"
      @init-api="(val) => carouselApi = val"
    >
      <!-- Positioning context for the arrows. The photo panel is a separate child so
           its overflow-hidden clips the photos without clipping the arrows. -->
      <div class="relative mx-auto w-full">
        <div class="overflow-hidden rounded-2xl shadow-xl ring-1 ring-zinc-900/10 md:rounded-3xl dark:ring-white/10">
          <CarouselContent class="ml-0">
            <!-- `:inert` uses `|| undefined` so the attribute is fully removed on the
                 active slide — a literal inert="false" attribute still inert-ifies per
                 the HTML spec. -->
            <CarouselItem
              v-for="(slide, index) in element.json_content"
              :key="index"
              class="pl-0"
              :inert="index !== currentSlide || undefined"
              :aria-hidden="index !== currentSlide ? 'true' : undefined"
              :aria-label="$t('accessibility.carousel_slide_position', { current: String(index + 1), total: String(element.json_content.length) })"
            >
              <div :class="['relative flex', slideHeightClass]">
                <!-- Background photo -->
                <img
                  :src="slide.imageSrc"
                  :alt="slide.imageAlt"
                  :style="slide.objectPosition ? { objectPosition: slide.objectPosition } : undefined"
                  class="absolute inset-0 h-full w-full object-cover"
                  :loading="index === 0 ? 'eager' : 'lazy'"
                  :fetchpriority="index === 0 ? 'high' : undefined"
                  draggable="false"
                >

                <!-- Scrim: a uniform strength layer plus, for bottom-anchored text, a
                     bottom-up gradient. Without it, overlaid white text sinks into busy
                     photos. -->
                <div :class="['absolute inset-0', SCRIM_BASE_CLASS[scrimStrength]]" />
                <div v-if="slideAlign(slide) !== 'center'" class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-zinc-950/25 to-transparent" />

                <!-- Overlaid text block -->
                <div :class="slideContainerClass(slideAlign(slide))">
                  <div :class="['max-w-2xl space-y-3 sm:space-y-4', slideAlign(slide) === 'center' && 'mx-auto text-center']">
                    <p v-if="slide.eyebrow" class="text-xs font-semibold uppercase tracking-wider text-vusa-yellow">
                      {{ slide.eyebrow }}
                    </p>
                    <h2 class="text-3xl font-bold leading-tight text-white drop-shadow-sm sm:text-4xl lg:text-5xl">
                      {{ slide.title }}
                    </h2>
                    <p v-if="slide.subtitle" class="text-base text-zinc-100 drop-shadow-sm sm:text-lg lg:text-xl">
                      {{ slide.subtitle }}
                    </p>
                    <!-- Authored as Tiptap JSON; rendered client-side like CarouselSlideDeck.
                         Text color is forced through [&_a] etc. because rc-prose's link blue
                         is unreadable on dark photos. -->
                    <div
                      v-if="hasTiptapContent(slide.description)"
                      class="rc-prose text-sm text-zinc-200 leading-relaxed sm:text-base [&_a]:text-white [&_a]:underline [&_strong]:text-white"
                    >
                      <RichContentTiptapHTML :json_content="slide.description" />
                    </div>
                    <HeroButtons :buttons="slide.buttons" class="pt-2 sm:pt-4" />
                  </div>
                </div>
              </div>
            </CarouselItem>
          </CarouselContent>
        </div>

        <!-- Arrows straddle the panel edge, in the section's gutter — off the photo
             content. Hidden below sm (the gutter is 16px there); the carousel stays
             swipeable and dotted. Slot overrides the hardcoded English sr-only. -->
        <CarouselPrevious
          v-if="arrowsEnabled && hasMultipleSlides"
          :class="[
            'hidden sm:flex -left-4 lg:-left-6 size-9 md:size-10',
            'bg-white text-zinc-900 border-zinc-200 hover:bg-zinc-50 hover:border-zinc-300',
            'dark:bg-zinc-800 dark:text-zinc-100 dark:border-zinc-600 dark:hover:bg-zinc-700 dark:hover:border-zinc-500',
          ]"
          @click="restartCarouselAutoplay"
        >
          <ArrowLeft />
          <span class="sr-only">{{ $t('accessibility.carousel_previous_slide') }}</span>
        </CarouselPrevious>
        <CarouselNext
          v-if="arrowsEnabled && hasMultipleSlides"
          :class="[
            'hidden sm:flex -right-4 lg:-right-6 size-9 md:size-10',
            'bg-white text-zinc-900 border-zinc-200 hover:bg-zinc-50 hover:border-zinc-300',
            'dark:bg-zinc-800 dark:text-zinc-100 dark:border-zinc-600 dark:hover:bg-zinc-700 dark:hover:border-zinc-500',
          ]"
          @click="restartCarouselAutoplay"
        >
          <ArrowRight />
          <span class="sr-only">{{ $t('accessibility.carousel_next_slide') }}</span>
        </CarouselNext>
      </div>

      <!-- Dot indicators, below the panel on the page background — never on top of
           slide content. -->
      <div
        v-if="indicatorsEnabled && hasMultipleSlides"
        class="mt-3 flex justify-center gap-2 sm:mt-4"
      >
        <button
          v-for="(slide, index) in element.json_content"
          :key="index"
          type="button"
          class="h-2.5 w-2.5 rounded-full transition-all duration-200"
          :class="currentSlide === index ? 'bg-vusa-red' : 'bg-zinc-300 hover:bg-zinc-400 dark:bg-zinc-600 dark:hover:bg-zinc-500'"
          :aria-label="$t('accessibility.carousel_go_to_slide', { index: String(index + 1) })"
          :aria-current="currentSlide === index ? 'true' : undefined"
          @click="() => { carouselApi?.scrollTo(index); restartCarouselAutoplay(); }"
        />
      </div>
    </Carousel>

    <!-- Screen-reader announcement of the visible slide -->
    <p class="sr-only" aria-live="polite">
      {{ $t('accessibility.carousel_slide_position', { current: String(currentSlide + 1), total: String(element.json_content.length) }) }}
    </p>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import HeroButtons from '../RCHeroSection/HeroButtons.vue';
import { asBoolean } from '../booleanish';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import type { HeroCarousel } from '@/Types/contentParts';

const props = defineProps<{
  element: HeroCarousel;
  isFirstElement?: boolean;
  anchorId?: number | null;
}>();

const SCRIM_BASE_CLASS = {
  light: 'bg-zinc-950/20',
  medium: 'bg-zinc-950/40',
  dark: 'bg-zinc-950/60',
} as const;

// Authorable panel height (options.height) — svh on mobile so browser chrome
// collapsing doesn't crop the text, vh + min-h floors on larger screens.
const HEIGHT_CLASS = {
  sm: 'h-[42svh] min-h-[18rem] md:h-[48vh] xl:h-[52vh]',
  md: 'h-[55svh] min-h-[22rem] md:h-[62vh] xl:h-[68vh]',
  lg: 'h-[68svh] min-h-[26rem] md:h-[76vh] xl:h-[82vh]',
} as const;

const carouselApi = ref();
const currentSlide = ref(0);

const hasMultipleSlides = computed(() => props.element.json_content.length > 1);
const scrimStrength = computed(() => props.element.options?.scrim ?? 'medium');
const slideHeightClass = computed(() => HEIGHT_CLASS[props.element.options?.height ?? 'md']);
const arrowsEnabled = computed(() => asBoolean(props.element.options?.showArrows));
const indicatorsEnabled = computed(() => asBoolean(props.element.options?.showIndicators));

function slideAlign(slide: HeroCarousel['json_content'][number]): 'start' | 'center' | 'end' {
  return slide.align ?? 'start';
}

// One source for the text-block container classes — alignment changes both the
// cross-axis (items-*) and, for `center`, the main-axis (justify-*) position, so
// merging a shared `justify-end` with a per-align `justify-center` would leave two
// conflicting utilities on the element.
function slideContainerClass(align: 'start' | 'center' | 'end'): string {
  const base = 'relative z-10 flex h-full w-full flex-col px-6 sm:px-10 lg:px-14';
  const map = {
    start: 'justify-end items-start pb-10 text-left sm:pb-12',
    center: 'items-center justify-center text-center',
    end: 'justify-end items-end pb-10 text-right sm:pb-12',
  } as const;
  return [base, map[align]].join(' ');
}

function hasTiptapContent(description: HeroCarousel['json_content'][number]['description']): boolean {
  return Boolean(description && Array.isArray(description.content) && description.content.length > 0);
}

// Autoplay: one interval + one pending-restart timeout, both tracked so a quick
// focusout→focusin hop cannot leave a stray timeout running (the deck carousel's
// version has that race — copied here with the fix, not verbatim).
let autoplayInterval: ReturnType<typeof setInterval> | null = null;
let restartTimeout: ReturnType<typeof setTimeout> | null = null;

// jsdom has no matchMedia; Inertia SSR has no window at setup. Resolved in
// onMounted (client-only), so only the jsdom case needs the guard.
let reducedMotionQuery: MediaQueryList | null = null;

const autoplayDelay = () => props.element.options?.autoplayDelay || 8000;

const startCarouselAutoplay = () => {
  if (!asBoolean(props.element.options?.autoplay) || !hasMultipleSlides.value || !carouselApi.value) return;
  if (reducedMotionQuery?.matches) return;
  if (autoplayInterval) return;

  autoplayInterval = setInterval(() => carouselApi.value?.scrollNext(), autoplayDelay());
};

const stopCarouselAutoplay = () => {
  if (autoplayInterval) {
    clearInterval(autoplayInterval);
    autoplayInterval = null;
  }
  if (restartTimeout) {
    clearTimeout(restartTimeout);
    restartTimeout = null;
  }
};

const restartCarouselAutoplay = () => {
  stopCarouselAutoplay();
  restartTimeout = setTimeout(() => startCarouselAutoplay(), autoplayDelay());
};

watch(carouselApi, (api) => {
  if (!api) return;

  currentSlide.value = api.selectedScrollSnap();

  api.on('select', () => {
    currentSlide.value = api.selectedScrollSnap();
  });

  startCarouselAutoplay();

  // A drag (pointer) pauses; releasing restarts after a full delay.
  api.on('pointerDown', stopCarouselAutoplay);
  api.on('pointerUp', restartCarouselAutoplay);
});

onMounted(() => {
  if (typeof window.matchMedia === 'function') {
    reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    reducedMotionQuery.addEventListener('change', (event) => {
      if (event.matches) stopCarouselAutoplay();
    });
  }
});

onUnmounted(() => {
  stopCarouselAutoplay();
});
</script>
