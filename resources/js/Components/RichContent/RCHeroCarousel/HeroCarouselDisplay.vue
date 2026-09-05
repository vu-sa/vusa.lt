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
    <!-- Single slide: rendered as a pure static full-bleed hero -->
    <HeroCarouselSlideView
      v-if="slides.length === 1"
      :slide="slides[0]"
      :slide-index="0"
      :slide-height-class
      :scrim-strength
      is-first-slide
      :editable
      :block-key
      :can-delete-slide="false"
      @update:slide="updateSlide(0, $event)"
    />

    <!-- Multiple slides: rendered via Carousel -->
    <template v-else-if="slides.length > 1">
      <!-- Hover/focus pause lands on the Carousel (its root is a role="region"
           tabindex="0" element, so these interactions have an accessible target);
           listeners fall through to it from here. -->
      <Carousel
        class="w-full"
        :opts="{ loop: true }"
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
            v-for="(slide, index) in slides"
            :key="index"
            class="pl-0"
            :inert="index !== currentSlide || undefined"
            :aria-hidden="index !== currentSlide ? 'true' : undefined"
            :aria-label="
              $t('accessibility.carousel_slide_position', { current: String(index + 1), total: String(slides.length) })
            "
          >
            <HeroCarouselSlideView
              :slide
              :slide-index="index"
              :slide-height-class
              :scrim-strength
              :is-first-slide="index === 0"
              :editable
              :block-key
              :can-delete-slide="editable && slides.length > 1"
              @update:slide="updateSlide(index, $event)"
              @delete-slide="removeSlide(index)"
            />
          </CarouselItem>
        </CarouselContent>
      </Carousel>

      <!-- Controls sit in one bottom bar rather than straddling the photo: dots left, arrows
           right, both inside the content measure so they line up with the copy above them.
           The bar itself is click-through so it never steals a drag from the carousel. -->
      <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 pb-5 sm:px-6 lg:px-8">
          <div v-if="indicatorsEnabled" class="pointer-events-auto flex items-center gap-2">
            <button
              v-for="(slide, index) in slides"
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
              :class="[
                'flex size-10 items-center justify-center border border-white/25 bg-ink/60 text-white/95',
                'backdrop-blur transition-colors hover:border-brand-fill hover:text-brand-fill',
              ]"
              @click="() => { carouselApi?.scrollPrev(); restartCarouselAutoplay(); }"
            >
              <IFluentArrowLeft24Regular class="size-4" />
              <span class="sr-only">{{ $t('accessibility.carousel_previous_slide') }}</span>
            </button>
            <button
              type="button"
              :class="[
                'flex size-10 items-center justify-center border border-white/25 bg-ink/60 text-white/95',
                'backdrop-blur transition-colors hover:border-brand-fill hover:text-brand-fill',
              ]"
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
        {{
          $t('accessibility.carousel_slide_position', {
            current: String(currentSlide + 1),
            total: String(slides.length),
          })
        }}
      </p>
    </template>

    <!-- Zero slides empty state in edit mode -->
    <div
      v-else-if="editable"
      :class="['relative flex flex-col items-center justify-center p-8 text-center text-white/60', slideHeightClass]"
    >
      <p class="mb-4 text-sm">
        {{ $t('rich-content.no_slides') }}
      </p>
      <Button variant="outline" size="sm" @click="addSlide">
        <IFluentAdd12Regular class="mr-1 size-3.5" />
        {{ $t('rich-content.add_slide') }}
      </Button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { asBoolean } from '../booleanish';
import type { BandResolution } from '../bandLayout';

import HeroCarouselSlideView from './HeroCarouselSlideView.vue';

import { Button } from '@/Components/ui/button';
import { Carousel, CarouselContent, CarouselItem } from '@/Components/ui/carousel';
import type { HeroCarousel } from '@/Types/contentParts';
import IFluentAdd12Regular from '~icons/fluent/add12-regular';
import IFluentArrowLeft24Regular from '~icons/fluent/arrow-left24-regular';
import IFluentArrowRight24Regular from '~icons/fluent/arrow-right24-regular';

type Slide = HeroCarousel['json_content'][number];

defineOptions({ inheritAttrs: false });

const props = defineProps<{
  element: HeroCarousel;
  isFirstElement?: boolean;
  anchorId?: number | null;
  editable?: boolean;
  blockKey?: string;
  band?: BandResolution;
}>();

const emit = defineEmits<(e: 'update:element', value: HeroCarousel) => void>();

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

const slides = computed<Slide[]>(() => (Array.isArray(props.element.json_content) ? props.element.json_content : []));
const hasMultipleSlides = computed(() => slides.value.length > 1);
const scrimStrength = computed(() => props.element.options?.scrim ?? 'medium');
const slideHeightClass = computed(() => HEIGHT_CLASS[props.element.options?.height ?? 'md']);
const arrowsEnabled = computed(() => asBoolean(props.element.options?.showArrows ?? true));
const indicatorsEnabled = computed(() => asBoolean(props.element.options?.showIndicators ?? true));

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
    eyebrow: '',
    title: '',
    subtitle: '',
    description: '',
    imageSrc: '',
    imageAlt: '',
    align: 'start',
    buttons: [],
  };
  emit('update:element', {
    ...props.element,
    json_content: [...slides.value, newSlide],
  });
}

// Autoplay: one interval + one pending-restart timeout, both tracked so a quick
// focusout→focusin hop cannot leave a stray timeout running.
let autoplayInterval: ReturnType<typeof setInterval> | null = null;
let restartTimeout: ReturnType<typeof setTimeout> | null = null;

// jsdom has no matchMedia; Inertia SSR has no window at setup. Resolved in
// onMounted (client-only), so only the jsdom case needs the guard.
let reducedMotionQuery: MediaQueryList | null = null;

const autoplayDelay = () => props.element.options?.autoplayDelay || 8000;

const startCarouselAutoplay = () => {
  if (props.editable) return;
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
  if (props.editable) return;
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

watch(() => props.editable, (isEditable) => {
  if (isEditable) {
    stopCarouselAutoplay();
  }
  else {
    startCarouselAutoplay();
  }
});

watch(() => slides.value.length, (length) => {
  if (length <= 1) {
    stopCarouselAutoplay();
  }
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
