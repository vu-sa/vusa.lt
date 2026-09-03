<template>
  <!-- Edge-to-edge band, no inset panel and no rounding: the hero is the one element that
       breaks every measure on the page, so the photograph runs to the viewport edges and only
       the copy inside it keeps the content measure. `rc-viewport` is what escapes PublicLayout's
       `.container` column (see app.css); `-mt-*` cancels the content wrapper's top padding so
       the band sits flush under the fixed header. -->
  <section
    :id="anchorId ? `rc-${anchorId}` : undefined"
    :class="[
      'rc-viewport relative isolate scroll-mt-32 overflow-hidden border-b border-border bg-ink',
      isFirstElement && '-mt-4 md:-mt-6 lg:-mt-8',
    ]"
  >
    <!-- Hover/focus pause lands on the Carousel (its root is a role="region"
         tabindex="0" element, so these interactions have an accessible target);
         listeners fall through to it from here. -->
    <Carousel
      class="w-full"
      :opts="{ loop: hasMultipleSlides }"
      @mouseenter="stopCarouselAutoplay"
      @mouseleave="restartCarouselAutoplay"
      @focusin="stopCarouselAutoplay"
      @focusout="restartCarouselAutoplay"
      @init-api="(val) => carouselApi = val"
    >
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
          <div :class="['relative', slideHeightClass]">
            <!-- Grayscale is the house treatment wherever type sits on a photograph: it
                 drops the picture a layer back so the headline is the loudest thing. -->
            <img
              :src="slide.imageSrc"
              :alt="slide.imageAlt"
              :style="slide.objectPosition ? { objectPosition: slide.objectPosition } : undefined"
              :class="['absolute inset-0 size-full object-cover object-center grayscale', SCRIM_IMAGE_OPACITY[scrimStrength]]"
              :loading="index === 0 ? 'eager' : 'lazy'"
              :fetchpriority="index === 0 ? 'high' : undefined"
              draggable="false"
            >

            <!-- Two scrims, not one: the horizontal pass carries the left-anchored copy, the
                 vertical pass keeps the bottom control bar off a bright patch of sky. -->
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/80 to-ink/25" />
            <div class="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-ink/40" />

            <!-- Overlaid text block -->
            <div :class="slideContainerClass(slideAlign(slide))">
              <div :class="slideCopyClass(slideAlign(slide))">
                <EyebrowLabel v-if="slide.eyebrow" class="text-brand-fill">
                  {{ slide.eyebrow }}
                </EyebrowLabel>
                <h2 class="u-display mt-4 text-pretty text-[2.25rem] text-white sm:text-6xl lg:text-7xl">
                  {{ slide.title }}
                </h2>
                <p v-if="slide.subtitle" class="mt-5 max-w-xl text-pretty leading-relaxed text-white/85">
                  {{ slide.subtitle }}
                </p>
                <!-- Authored as Tiptap JSON; rendered client-side like CarouselSlideDeck.
                     `rc-prose` sets its own (theme-following) text/link color through unlayered
                     rules that outrank a plain `text-white/…` utility here — `rc-prose-invert`
                     (typography.css) gives it a fixed-white palette instead, matching the scrim
                     it sits on in both themes. -->
                <div
                  v-if="hasTiptapContent(slide.description)"
                  class="rc-prose rc-prose-invert mt-3 max-w-xl text-sm leading-relaxed sm:text-base"
                >
                  <RichContentTiptapHTML :json_content="slide.description" />
                </div>
                <HeroButtons :buttons="slide.buttons" class="mt-7" />
              </div>
            </div>
          </div>
        </CarouselItem>
      </CarouselContent>
    </Carousel>

    <!-- Controls sit in one bottom bar rather than straddling the photo: dots left, arrows
         right, both inside the content measure so they line up with the copy above them.
         The bar itself is click-through so it never steals a drag from the carousel. -->
    <div v-if="hasMultipleSlides" class="pointer-events-none absolute inset-x-0 bottom-0 z-10">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-5 pb-5 sm:px-6 lg:px-8">
        <div v-if="indicatorsEnabled" class="pointer-events-auto flex items-center gap-2">
          <button
            v-for="(slide, index) in element.json_content"
            :key="index"
            type="button"
            :class="[
              'h-1 transition-all duration-200',
              currentSlide === index ? 'w-8 bg-brand-fill' : 'w-4 bg-white/40 hover:bg-white/70',
            ]"
            :aria-label="$t('accessibility.carousel_go_to_slide', { index: String(index + 1) })"
            :aria-current="currentSlide === index ? 'true' : undefined"
            @click="() => { carouselApi?.scrollTo(index); restartCarouselAutoplay(); }"
          />
        </div>
        <!-- Placeholder keeps the arrows right-aligned when indicators are switched off. -->
        <span v-else />

        <div v-if="arrowsEnabled" class="pointer-events-auto flex items-center gap-2">
          <button
            type="button"
            class="flex size-10 items-center justify-center border border-white/25 bg-ink/60 text-white/95 backdrop-blur transition-colors hover:border-brand-fill hover:text-brand-fill"
            @click="() => { carouselApi?.scrollPrev(); restartCarouselAutoplay(); }"
          >
            <IFluentArrowLeft24Regular class="size-4" />
            <span class="sr-only">{{ $t('accessibility.carousel_previous_slide') }}</span>
          </button>
          <button
            type="button"
            class="flex size-10 items-center justify-center border border-white/25 bg-ink/60 text-white/95 backdrop-blur transition-colors hover:border-brand-fill hover:text-brand-fill"
            @click="() => { carouselApi?.scrollNext(); restartCarouselAutoplay(); }"
          >
            <IFluentArrowRight24Regular class="size-4" />
            <span class="sr-only">{{ $t('accessibility.carousel_next_slide') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Screen-reader announcement of the visible slide -->
    <p class="sr-only" aria-live="polite">
      {{ $t('accessibility.carousel_slide_position', { current: String(currentSlide + 1), total: String(element.json_content.length) }) }}
    </p>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import HeroButtons from '../RCHeroSection/HeroButtons.vue';
import { asBoolean } from '../booleanish';
import { Carousel, CarouselContent, CarouselItem } from '@/Components/ui/carousel';
import { EyebrowLabel } from '@/Components/Public/Base';
import type { HeroCarousel } from '@/Types/contentParts';

const props = defineProps<{
  element: HeroCarousel;
  isFirstElement?: boolean;
  anchorId?: number | null;
}>();

/**
 * `scrim` reads as how far back the photograph sits, so it drives the image's own opacity
 * against the ink ground rather than stacking another translucent sheet on top. Fewer layers,
 * and the gradients above stay at one strength whatever the author picks.
 */
const SCRIM_IMAGE_OPACITY = {
  light: 'opacity-85',
  medium: 'opacity-70',
  dark: 'opacity-55',
} as const;

/**
 * Authorable band height (options.height) — a floor, not a viewport fraction. Tying the hero
 * to `vh` made it grow with the window until it swallowed a tall desktop screen whole; the
 * design fixes the band and lets the copy sit in it, so a wide monitor sees the hero *and* the
 * news section below it.
 */
const HEIGHT_CLASS = {
  sm: 'min-h-[28rem] sm:min-h-[30rem] lg:min-h-[32rem]',
  md: 'min-h-[34rem] sm:min-h-[36rem] lg:min-h-[40rem]',
  lg: 'min-h-[40rem] sm:min-h-[44rem] lg:min-h-[48rem]',
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
// conflicting utilities on the element. Bottom padding clears the control bar.
function slideContainerClass(align: 'start' | 'center' | 'end'): string {
  const base = 'relative z-10 mx-auto flex min-h-[inherit] max-w-7xl flex-col px-5 pt-24 sm:px-6 lg:px-8';
  const map = {
    start: 'justify-end items-start pb-20 text-left sm:pb-24',
    center: 'items-center justify-center pb-16 text-center',
    end: 'justify-end items-end pb-20 text-right sm:pb-24',
  } as const;
  return [base, map[align]].join(' ');
}

/**
 * The brand rule hangs off whichever edge the copy is anchored to. Centred copy gets none —
 * a rule only reads as a rule when the type it marks is flush against it.
 */
function slideCopyClass(align: 'start' | 'center' | 'end'): string {
  const map = {
    start: 'border-l-2 border-brand-fill pl-5 sm:pl-7',
    center: 'mx-auto',
    end: 'border-r-2 border-brand-fill pr-5 sm:pr-7',
  } as const;
  return ['max-w-2xl', map[align]].join(' ');
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
