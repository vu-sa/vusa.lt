<template>
  <!-- Width (prose/content/wide/full) is decided by the parser wrapper via options.width. -->

  <!-- split (default): two-column text + image, the original hero layout. -->
  <section v-if="variant === 'split'" :id="anchorElementId"
    :class="[...(band?.classes ?? []), isFirstElement && '-mt-4 md:-mt-6 lg:-mt-8']">
    <div class="max-w-6xl mx-auto px-4 relative z-10">
      <div class="grid 2xl:grid-cols-2 gap-6 sm:gap-8 md:gap-10 lg:gap-12 xl:gap-14 2xl:gap-16 items-center">
        <div :class="['space-y-4 sm:space-y-5 md:space-y-6 2xl:space-y-8 2xl:pr-8', element.options?.textLeft ? 'order-first' : 'order-last 2xl:order-first']">
          <div class="space-y-3 sm:space-y-4 md:space-y-5 2xl:space-y-6">
            <slot name="eyebrow" :eyebrow="element.json_content.eyebrow">
              <EyebrowLabel v-if="element.json_content.eyebrow">
                <span>{{ element.json_content.eyebrow }}</span>
              </EyebrowLabel>
            </slot>
            <slot name="title" :title="element.json_content.title">
              <div role="heading" aria-level="1" :class="[titleClass, titleAlignmentClass]">
                <span v-html="element.json_content.title" />
              </div>
            </slot>
            <slot name="description" :description="element.json_content.description">
              <p v-if="hasHtmlText(element.json_content.description)" :class="descriptionClass">
                <span v-html="element.json_content.description" />
              </p>
            </slot>
          </div>

          <slot name="buttons" :buttons="element.json_content.buttons">
            <HeroButtons :buttons="element.json_content.buttons" />
          </slot>
        </div>

        <div :class="['relative', element.options?.textLeft ? 'order-last' : 'order-first 2xl:order-last']">
          <slot name="image">
            <ImageWithDecorations
              :src="element.json_content.imageSrc"
              :alt="element.json_content.imageAlt"
              height-class="aspect-[16/10] h-auto"
              :decorations="element.options?.imageDecorations"
              :overlay-content="element.json_content.overlayContent"
              :overlay-corner="element.json_content.overlayCorner"
              :overlay-overhang="element.json_content.overlayOverhang"
              :overlay-padding="element.json_content.overlayPadding"
              :object-position="element.json_content.objectPosition"
              loading="eager"
            />
          </slot>
        </div>
      </div>
    </div>
  </section>

  <!-- centered: no image, centred title/description/buttons — the CTA/slogan shape. -->
  <section v-else-if="variant === 'centered'" :id="anchorElementId" :class="band?.classes ?? []">
    <div class="max-w-3xl mx-auto px-4 relative z-10 text-center">
      <slot name="eyebrow" :eyebrow="element.json_content.eyebrow">
        <EyebrowLabel v-if="element.json_content.eyebrow">
          <span>{{ element.json_content.eyebrow }}</span>
        </EyebrowLabel>
      </slot>
      <slot name="title" :title="element.json_content.title">
        <div role="heading" aria-level="1" :class="[titleClass, titleAlignmentClass]">
          <span v-html="element.json_content.title" />
        </div>
      </slot>
      <slot name="description" :description="element.json_content.description">
        <p v-if="hasHtmlText(element.json_content.description)" :class="descriptionClass">
          <span v-html="element.json_content.description" />
        </p>
      </slot>
      <slot name="buttons" :buttons="element.json_content.buttons">
        <HeroButtons :buttons="element.json_content.buttons" :class="buttonsClass" />
      </slot>
    </div>
  </section>

  <!-- banner ("juosta"): compact full-width strip — a single row, title + one button.
       Swaps in the compact padding (py-8, not the standard py-16) — a single-row strip
       flattened to the same rhythm as every other band would look wrong. -->
  <section v-else-if="variant === 'banner'" :id="anchorElementId" :class="bannerClasses">
    <div class="max-w-6xl mx-auto px-4 relative z-10 flex flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
      <slot name="title" :title="element.json_content.title">
        <div role="heading" aria-level="2" :class="[titleClass, titleAlignmentClass]">
          <span v-html="element.json_content.title" />
        </div>
      </slot>
      <slot name="buttons" :buttons="element.json_content.buttons">
        <HeroButtons :buttons="element.json_content.buttons?.slice(0, 1)" />
      </slot>
    </div>
  </section>

  <!-- panel: the SummerCamps hero — a ruled panel and a square thumbnail, kept short so page
       content below stays reachable without scrolling. -->
  <section v-else :id="anchorElementId" class="relative scroll-mt-32">
    <div class="relative border border-border bg-secondary/40 p-5 sm:p-6">
      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
        <slot name="image">
          <img
            v-if="element.json_content.imageSrc"
            :src="element.json_content.imageSrc"
            :alt="element.json_content.imageAlt"
            :style="element.json_content.objectPosition ? { objectPosition: element.json_content.objectPosition } : undefined"
            class="hidden aspect-square w-32 shrink-0 border border-border object-cover sm:block lg:w-40"
            loading="lazy"
          >
        </slot>

        <div class="min-w-0">
          <slot name="eyebrow" :eyebrow="element.json_content.eyebrow">
            <EyebrowLabel v-if="element.json_content.eyebrow">
              <span>{{ element.json_content.eyebrow }}</span>
            </EyebrowLabel>
          </slot>
          <slot name="title" :title="element.json_content.title">
            <div role="heading" aria-level="1" :class="[titleClass, titleAlignmentClass]">
              <span v-html="element.json_content.title" />
            </div>
          </slot>
          <slot name="description" :description="element.json_content.description">
            <p v-if="hasHtmlText(element.json_content.description)" :class="descriptionClass">
              <span v-html="element.json_content.description" />
            </p>
          </slot>

          <slot name="buttons" :buttons="element.json_content.buttons">
            <HeroButtons :buttons="element.json_content.buttons" :class="buttonsClass" />
          </slot>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
/**
 * Pure public display for the `hero` block — owns structure, classes, variants and
 * presentation calculations only. Every author-editable spot (eyebrow/title/description/
 * buttons/image) is a scoped slot with the public rendering as its default content, so
 * the full-screen editor's `HeroEditableElement.vue` can wrap this component and replace
 * only those leaves, instead of forking the whole template. Never imports anything from
 * `Editor/Fullscreen` or TipTap: public visitors never download the editing machinery.
 */
import { computed } from 'vue';

import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import HeroButtons from './HeroButtons.vue';
import { EyebrowLabel } from '@/Components/Public/Base';
import type { Hero } from '@/Types/contentParts';
import { hasHtmlText } from '@/Utils/String';
import { withCompactPadding, type BandResolution } from '../bandLayout';
import { heroButtonsClass, heroDescriptionClass, heroTitleAlignmentClass, heroTitleClass } from './heroLayout';

// `inlineEditable: true` makes BlockPreviewRenderer start passing real values for props
// this component doesn't declare (it has no server-resolved data, so never needs the
// others). Without this, an undeclared non-undefined fallthrough attribute would land on
// whichever <section> variant is currently active.
defineOptions({ inheritAttrs: false });

const props = defineProps<{
  element: Hero;
  isFirstElement: boolean;
  anchorId?: number | null;
  /** Undefined for `panel` — its `bandRole` resolves to `'flow'` (see Types/index.ts),
   *  so it never receives a chrome resolution and keeps its own fixed gradient-panel look. */
  band?: BandResolution;
}>();

const variant = computed(() => props.element.options?.variant ?? 'split');
const anchorElementId = computed(() => (props.anchorId ? `rc-${props.anchorId}` : undefined));

const bannerClasses = computed(() => (props.band ? withCompactPadding(props.band).classes : []));
const titleClass = computed(() => heroTitleClass(variant.value));
const titleAlignmentClass = computed(() => heroTitleAlignmentClass(variant.value));
const descriptionClass = computed(() => heroDescriptionClass(variant.value));
const buttonsClass = computed(() => heroButtonsClass(variant.value));
</script>

<style scoped>
.rc-hero-title > button {
  font: inherit;
  text-align: inherit;
  text-transform: inherit;
  letter-spacing: inherit;
  line-height: inherit;
}

.rc-hero-title :deep(:is(p, h2)) {
  margin: 0;
  font: inherit;
  color: inherit;
  text-align: inherit;
  text-transform: uppercase;
  letter-spacing: inherit;
  line-height: inherit;
}
</style>
