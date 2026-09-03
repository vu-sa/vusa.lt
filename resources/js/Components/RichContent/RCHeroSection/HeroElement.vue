<template>
  <!-- Width (prose/content/wide/full) is decided by the parser wrapper via options.width. -->

  <!-- split (default): two-column text + image, the original hero layout. -->
  <section v-if="variant === 'split'" :id="anchorElementId"
    :class="['relative scroll-mt-32 overflow-hidden', isFirstElement && '-mt-4 md:-mt-6 lg:-mt-8', backgroundClass, splitPaddingClass, roundedClass]">
    <div class="max-w-6xl mx-auto px-4 relative z-10">
      <div class="grid 2xl:grid-cols-2 gap-6 sm:gap-8 md:gap-10 lg:gap-12 xl:gap-14 2xl:gap-16 items-center">
        <div :class="['space-y-4 sm:space-y-5 md:space-y-6 2xl:space-y-8 2xl:pr-8', element.options?.textLeft ? 'order-first' : 'order-last 2xl:order-first']">
          <div class="space-y-3 sm:space-y-4 md:space-y-5 2xl:space-y-6">
            <EyebrowLabel v-if="element.json_content.eyebrow">
              {{ element.json_content.eyebrow }}
            </EyebrowLabel>
            <h1
              class="u-display text-3xl text-foreground sm:text-4xl md:text-5xl lg:text-6xl"
              v-html="element.json_content.title"
            />
            <p class="max-w-lg text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg lg:text-xl">
              {{ element.json_content.description }}
            </p>
          </div>

          <HeroButtons :buttons="element.json_content.buttons" />
        </div>

        <div :class="['relative', element.options?.textLeft ? 'order-last' : 'order-first 2xl:order-last']">
          <ImageWithDecorations
            :src="element.json_content.imageSrc"
            :alt="element.json_content.imageAlt"
            height-class="h-[240px] sm:h-[280px] md:h-[320px] lg:h-[360px] xl:h-[400px] 2xl:h-[500px]"
            :decorations="element.options?.imageDecorations"
            :overlay-content="element.json_content.overlayContent"
            :overlay-corner="element.json_content.overlayCorner"
            :overlay-overhang="element.json_content.overlayOverhang"
            :overlay-padding="element.json_content.overlayPadding"
            :object-position="element.json_content.objectPosition"
            loading="eager"
          />
        </div>
      </div>
    </div>
  </section>

  <!-- centered: no image, centred title/description/buttons — the CTA/slogan shape. -->
  <section v-else-if="variant === 'centered'" :id="anchorElementId"
    :class="['relative scroll-mt-32', backgroundClass, centeredPaddingClass, roundedClass]">
    <div class="max-w-3xl mx-auto px-4 relative z-10 text-center">
      <EyebrowLabel v-if="element.json_content.eyebrow">
        {{ element.json_content.eyebrow }}
      </EyebrowLabel>
      <h1
        class="u-display mt-3 text-3xl text-foreground sm:text-4xl md:text-5xl"
        v-html="element.json_content.title"
      />
      <p v-if="element.json_content.description" class="mt-4 text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg">
        {{ element.json_content.description }}
      </p>
      <HeroButtons :buttons="element.json_content.buttons" class="mt-6 justify-center" />
    </div>
  </section>

  <!-- banner ("juosta"): compact full-width strip — a single row, title + one button. -->
  <section v-else-if="variant === 'banner'" :id="anchorElementId"
    :class="['relative scroll-mt-32', backgroundClass, bannerPaddingClass, roundedClass]">
    <div class="max-w-6xl mx-auto px-4 relative z-10 flex flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
      <h2 class="u-display text-lg text-foreground sm:text-xl md:text-2xl" v-html="element.json_content.title" />
      <HeroButtons :buttons="element.json_content.buttons?.slice(0, 1)" />
    </div>
  </section>

  <!-- panel: the SummerCamps hero — a ruled panel and a square thumbnail, kept short so page
       content below stays reachable without scrolling. -->
  <section v-else :id="anchorElementId" class="relative scroll-mt-32">
    <div class="relative border border-border bg-secondary/40 p-5 sm:p-6">
      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
        <img
          v-if="element.json_content.imageSrc"
          :src="element.json_content.imageSrc"
          :alt="element.json_content.imageAlt"
          :style="element.json_content.objectPosition ? { objectPosition: element.json_content.objectPosition } : undefined"
          class="hidden aspect-square w-32 shrink-0 border border-border object-cover sm:block lg:w-40"
          loading="lazy"
        >

        <div class="min-w-0">
          <EyebrowLabel v-if="element.json_content.eyebrow">
            {{ element.json_content.eyebrow }}
          </EyebrowLabel>
          <h1 class="u-display mt-2 text-2xl text-foreground sm:text-3xl" v-html="element.json_content.title" />
          <p v-if="element.json_content.description" class="mt-2 max-w-prose text-sm leading-6 text-muted-foreground sm:text-base">
            {{ element.json_content.description }}
          </p>

          <HeroButtons :buttons="element.json_content.buttons" class="mt-4" />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import HeroButtons from './HeroButtons.vue';
import { EyebrowLabel } from '@/Components/Public/Base';
import type { Hero } from '@/Types/contentParts';
import { BACKGROUND_CLASS, PADDING_CLASS, ROUNDED_CLASS } from '../sectionClasses';

const props = defineProps<{
  element: Hero;
  isFirstElement: boolean;
  anchorId?: number | null;
}>();

const variant = computed(() => props.element.options?.variant ?? 'split');
const anchorElementId = computed(() => (props.anchorId ? `rc-${props.anchorId}` : undefined));

// `background`/`padding`/`rounded` are authorable options for the three variants that used to
// hardcode their own chrome (`panel` keeps its own fixed look). `background: undefined` defaults
// to `'muted'`, which is what every variant rendered before the options existed.
const backgroundClass = computed(() => BACKGROUND_CLASS[props.element.options?.background ?? 'muted']);
const roundedClass = computed(() => ROUNDED_CLASS[props.element.options?.rounded ?? 'none']);

// Padding defaults reproduce each variant's previous hardcoded value pixel-for-pixel;
// only diverge from it once an author explicitly picks a padding option.
const splitPaddingClass = computed(() => (props.element.options?.padding ? PADDING_CLASS[props.element.options.padding] : 'py-20'));
const centeredPaddingClass = computed(() => (props.element.options?.padding ? PADDING_CLASS[props.element.options.padding] : 'py-16 md:py-20'));
const bannerPaddingClass = computed(() => (props.element.options?.padding ? PADDING_CLASS[props.element.options.padding] : 'py-8'));
</script>
