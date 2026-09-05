<template>
  <!-- Width (prose/content/wide/full) is decided by the parser wrapper via options.width. -->

  <!-- split (default): two-column text + image, the original hero layout. -->
  <section v-if="variant === 'split'" :id="anchorElementId"
    :class="[...(band?.classes ?? []), isFirstElement && !editable && '-mt-4 md:-mt-6 lg:-mt-8']">
    <div class="max-w-6xl mx-auto px-4 relative z-10">
      <div class="grid 2xl:grid-cols-2 gap-6 sm:gap-8 md:gap-10 lg:gap-12 xl:gap-14 2xl:gap-16 items-center">
        <div :class="['space-y-4 sm:space-y-5 md:space-y-6 2xl:space-y-8 2xl:pr-8', element.options?.textLeft ? 'order-first' : 'order-last 2xl:order-first']">
          <div class="space-y-3 sm:space-y-4 md:space-y-5 2xl:space-y-6">
            <EyebrowLabel v-if="element.json_content.eyebrow || editable">
              <RCInlineText as="span" :model-value="element.json_content.eyebrow ?? ''" :editable :placeholder="$t('rich-content.eyebrow')" @update:model-value="updateJson({ eyebrow: $event })" />
            </EyebrowLabel>
            <div v-if="!isTitleLive" role="heading" aria-level="1" :class="['rc-hero-title u-display uppercase text-3xl text-foreground sm:text-4xl md:text-5xl lg:text-6xl', titleAlignmentClass]"
            >
              <button v-if="editable" type="button" class="block w-full cursor-text text-inherit" data-rc-interactive @click="claimTitle">
                <span v-if="hasTitle" v-html="element.json_content.title" />
                <span v-else class="text-muted-foreground/50">{{ $t('rich-content.title') }}</span>
              </button>
              <span v-else v-html="element.json_content.title" />
            </div>
            <div v-else :class="['rc-hero-title u-display uppercase text-3xl text-foreground sm:text-4xl md:text-5xl lg:text-6xl', titleAlignmentClass]" data-rc-interactive @focusout="releaseTitle">
              <TiptapEditor
                :model-value="element.json_content.title"
                preset="marks"
                toolbar="bubble"
                :show-bold="false"
                html
                class="rc-hero-title-editor u-display"
                :placeholder="$t('rich-content.title')"
                @update:model-value="updateTitle"
              />
            </div>
            <p v-if="!isDescriptionLive" class="max-w-lg text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg lg:text-xl">
              <button v-if="editable" type="button" class="block w-full cursor-text text-left" data-rc-interactive @click="claimDescription">
                <span v-if="hasDescription" v-html="element.json_content.description" />
                <span v-else class="text-muted-foreground/50">{{ $t('rich-content.description') }}</span>
              </button>
              <span v-else v-html="element.json_content.description" />
            </p>
            <TiptapEditor v-else
              :model-value="element.json_content.description ?? ''" preset="marks" toolbar="bubble" html
              class="rc-hero-description-editor max-w-lg text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg lg:text-xl"
              :placeholder="$t('rich-content.description')" data-rc-interactive
              @focusout="releaseDescription" @update:model-value="updateDescription"
            />
          </div>

          <HeroButtons v-if="!editable" :buttons="element.json_content.buttons" />
          <HeroButtonsEditable v-else :buttons="element.json_content.buttons" :block-key="blockKey ?? ''" :variant="variant" @update:buttons="updateButtons" />
        </div>

        <div :class="['relative', element.options?.textLeft ? 'order-last' : 'order-first 2xl:order-last']">
          <HeroImageHotspot v-if="editable" :content="element" :block-key="blockKey ?? ''" @update:content="$emit('update:element', $event)" />
          <ImageWithDecorations v-else
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
        </div>
      </div>
    </div>
  </section>

  <!-- centered: no image, centred title/description/buttons — the CTA/slogan shape. -->
  <section v-else-if="variant === 'centered'" :id="anchorElementId" :class="band?.classes ?? []">
    <div class="max-w-3xl mx-auto px-4 relative z-10 text-center">
      <EyebrowLabel v-if="element.json_content.eyebrow || editable">
        <RCInlineText as="span" :model-value="element.json_content.eyebrow ?? ''" :editable :placeholder="$t('rich-content.eyebrow')" @update:model-value="updateJson({ eyebrow: $event })" />
      </EyebrowLabel>
      <div v-if="!isTitleLive" role="heading" aria-level="1" :class="['rc-hero-title u-display uppercase mt-3 text-3xl text-foreground sm:text-4xl md:text-5xl', titleAlignmentClass]"
      >
        <button v-if="editable" type="button" class="block w-full cursor-text text-inherit" data-rc-interactive @click="claimTitle">
          <span v-if="hasTitle" v-html="element.json_content.title" />
          <span v-else class="text-muted-foreground/50">{{ $t('rich-content.title') }}</span>
        </button>
        <span v-else v-html="element.json_content.title" />
      </div>
      <div v-else :class="['rc-hero-title u-display uppercase mt-3 text-3xl text-foreground sm:text-4xl md:text-5xl', titleAlignmentClass]" data-rc-interactive @focusout="releaseTitle">
        <TiptapEditor
          :model-value="element.json_content.title"
          preset="marks"
          toolbar="bubble"
          :show-bold="false"
          html
          class="rc-hero-title-editor u-display"
          :placeholder="$t('rich-content.title')"
          @update:model-value="updateTitle"
        />
      </div>
      <p v-if="(hasDescription || editable) && !isDescriptionLive" class="mt-4 text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg">
        <button v-if="editable" type="button" class="block w-full cursor-text text-center" data-rc-interactive @click="claimDescription">
          <span v-if="hasDescription" v-html="element.json_content.description" />
          <span v-else class="text-muted-foreground/50">{{ $t('rich-content.description') }}</span>
        </button>
        <span v-else v-html="element.json_content.description" />
      </p>
      <TiptapEditor v-else-if="isDescriptionLive"
        :model-value="element.json_content.description ?? ''" preset="marks" toolbar="bubble" html
        class="rc-hero-description-editor mt-4 text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg"
        :placeholder="$t('rich-content.description')" data-rc-interactive
        @focusout="releaseDescription" @update:model-value="updateDescription"
      />
      <HeroButtons v-if="!editable" :buttons="element.json_content.buttons" class="mt-6 justify-center" />
      <HeroButtonsEditable v-else :buttons="element.json_content.buttons" :block-key="blockKey ?? ''" :variant="variant" class="mt-6 justify-center" @update:buttons="updateButtons" />
    </div>
  </section>

  <!-- banner ("juosta"): compact full-width strip — a single row, title + one button.
       Swaps in the compact padding (py-8, not the standard py-16) — a single-row strip
       flattened to the same rhythm as every other band would look wrong. -->
  <section v-else-if="variant === 'banner'" :id="anchorElementId" :class="bannerClasses">
    <div class="max-w-6xl mx-auto px-4 relative z-10 flex flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
      <div v-if="!isTitleLive" role="heading" aria-level="2" :class="['rc-hero-title u-display uppercase text-lg text-foreground sm:text-xl md:text-2xl', titleAlignmentClass]"
      >
        <button v-if="editable" type="button" class="block w-full cursor-text text-inherit" data-rc-interactive @click="claimTitle">
          <span v-if="hasTitle" v-html="element.json_content.title" />
          <span v-else class="text-muted-foreground/50">{{ $t('rich-content.title') }}</span>
        </button>
        <span v-else v-html="element.json_content.title" />
      </div>
      <div v-else :class="['rc-hero-title u-display uppercase text-lg text-foreground sm:text-xl md:text-2xl', titleAlignmentClass]" data-rc-interactive @focusout="releaseTitle">
        <TiptapEditor
          :model-value="element.json_content.title"
          preset="marks"
          toolbar="bubble"
          :show-bold="false"
          html
          class="rc-hero-title-editor u-display"
          :placeholder="$t('rich-content.title')"
          @update:model-value="updateTitle"
        />
      </div>
      <HeroButtons v-if="!editable" :buttons="element.json_content.buttons?.slice(0, 1)" />
      <HeroButtonsEditable v-else :buttons="element.json_content.buttons" :block-key="blockKey ?? ''" :variant="variant" @update:buttons="updateButtons" />
    </div>
  </section>

  <!-- panel: the SummerCamps hero — a ruled panel and a square thumbnail, kept short so page
       content below stays reachable without scrolling. -->
  <section v-else :id="anchorElementId" class="relative scroll-mt-32">
    <div class="relative border border-border bg-secondary/40 p-5 sm:p-6">
      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
        <HeroImageHotspot v-if="editable" :content="element" :block-key="blockKey ?? ''" @update:content="$emit('update:element', $event)" />
        <img
          v-else-if="element.json_content.imageSrc"
          :src="element.json_content.imageSrc"
          :alt="element.json_content.imageAlt"
          :style="element.json_content.objectPosition ? { objectPosition: element.json_content.objectPosition } : undefined"
          class="hidden aspect-square w-32 shrink-0 border border-border object-cover sm:block lg:w-40"
          loading="lazy"
        >

        <div class="min-w-0">
          <EyebrowLabel v-if="element.json_content.eyebrow || editable">
            <RCInlineText as="span" :model-value="element.json_content.eyebrow ?? ''" :editable :placeholder="$t('rich-content.eyebrow')" @update:model-value="updateJson({ eyebrow: $event })" />
          </EyebrowLabel>
          <div v-if="!isTitleLive" role="heading" aria-level="1" :class="['rc-hero-title u-display uppercase mt-2 text-2xl text-foreground sm:text-3xl', titleAlignmentClass]"
          >
            <button v-if="editable" type="button" class="block w-full cursor-text text-inherit" data-rc-interactive @click="claimTitle">
              <span v-if="hasTitle" v-html="element.json_content.title" />
              <span v-else class="text-muted-foreground/50">{{ $t('rich-content.title') }}</span>
            </button>
            <span v-else v-html="element.json_content.title" />
          </div>
          <div v-else :class="['rc-hero-title u-display uppercase mt-2 text-2xl text-foreground sm:text-3xl', titleAlignmentClass]" data-rc-interactive @focusout="releaseTitle">
            <TiptapEditor
              :model-value="element.json_content.title"
              preset="marks"
              toolbar="bubble"
              :show-bold="false"
              html
              class="rc-hero-title-editor u-display"
              :placeholder="$t('rich-content.title')"
              @update:model-value="updateTitle"
            />
          </div>
          <p v-if="(hasDescription || editable) && !isDescriptionLive" class="mt-2 max-w-prose text-sm leading-6 text-muted-foreground sm:text-base">
            <button v-if="editable" type="button" class="block w-full cursor-text text-left" data-rc-interactive @click="claimDescription">
              <span v-if="hasDescription" v-html="element.json_content.description" />
              <span v-else class="text-muted-foreground/50">{{ $t('rich-content.description') }}</span>
            </button>
            <span v-else v-html="element.json_content.description" />
          </p>
          <TiptapEditor v-else-if="isDescriptionLive"
            :model-value="element.json_content.description ?? ''" preset="marks" toolbar="bubble" html
            class="rc-hero-description-editor mt-2 max-w-prose text-sm leading-6 text-muted-foreground sm:text-base"
            :placeholder="$t('rich-content.description')" data-rc-interactive
            @focusout="releaseDescription" @update:model-value="updateDescription"
          />

          <HeroButtons v-if="!editable" :buttons="element.json_content.buttons" class="mt-4" />
          <HeroButtonsEditable v-else :buttons="element.json_content.buttons" :block-key="blockKey ?? ''" :variant="variant" class="mt-4" @update:buttons="updateButtons" />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, inject } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import HeroButtons from './HeroButtons.vue';
import HeroButtonsEditable from './HeroButtonsEditable.vue';
import HeroImageHotspot from './HeroImageHotspot.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';
import { EyebrowLabel } from '@/Components/Public/Base';
import type { Hero } from '@/Types/contentParts';
import { withCompactPadding, type BandResolution } from '../bandLayout';

// Lazy-loaded: title/description only ever mount a *live* TiptapEditor while claimed in
// the full-screen editor. A static import would bundle it into every public page that
// renders a hero, which never reaches that branch at all.
const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

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
  /** Full-screen editor mode: renders every text field click-to-edit in place and swaps
   *  the image/buttons for their editable-hotspot equivalents. Undefined/false in every
   *  other context (public rendering, forms-mode preview, the block picker). */
  editable?: boolean;
  /** This block's identity for hotspot ids (`${blockKey}:title`, `:image`, `:buttons:N`).
   *  Only meaningful — and only ever set — when `editable` is true. */
  blockKey?: string;
}>();

const emit = defineEmits<{
  (e: 'update:element', value: Hero): void;
}>();

// HeroElement renders both publicly and inside the full-screen editor, so — unlike a
// leaf hotspot that only ever mounts when editable — this injection must tolerate having
// no provider (public rendering) rather than throwing.
const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);

const variant = computed(() => props.element.options?.variant ?? 'split');
const anchorElementId = computed(() => (props.anchorId ? `rc-${props.anchorId}` : undefined));

const bannerClasses = computed(() => (props.band ? withCompactPadding(props.band).classes : []));
const titleAlignmentClass = computed(() => {
  if (variant.value === 'centered') return 'text-center';
  if (variant.value === 'banner') return 'text-center sm:text-left';

  return 'text-left';
});

const titleHotspotId = computed(() => `${props.blockKey ?? ''}:title`);
const descriptionHotspotId = computed(() => `${props.blockKey ?? ''}:description`);
const hasTitle = computed(() => elementText(props.element.json_content.title));
const hasDescription = computed(() => elementText(props.element.json_content.description));
const isTitleLive = computed(() => !!props.editable && !!hotspots?.isTextFieldLive(titleHotspotId.value));
const isDescriptionLive = computed(() => !!props.editable && !!hotspots?.isTextFieldLive(descriptionHotspotId.value));

function elementText(value: string | undefined): boolean {
  return Boolean(value?.replace(/<[^>]*>/g, '').trim());
}

function claimTitle(): void {
  if (props.editable) hotspots?.openTextField(titleHotspotId.value);
}

function releaseTitle(): void {
  hotspots?.close(titleHotspotId.value);
}

function updateTitle(value: string): void {
  updateJson({ title: value });
}

function claimDescription(): void {
  if (props.editable) hotspots?.openTextField(descriptionHotspotId.value);
}

function releaseDescription(): void {
  hotspots?.close(descriptionHotspotId.value);
}

function updateDescription(value: string): void {
  updateJson({ description: value });
}

function updateJson(patch: Partial<Hero['json_content']>): void {
  emit('update:element', { ...props.element, json_content: { ...props.element.json_content, ...patch } });
}

function updateButtons(buttons: Hero['json_content']['buttons']): void {
  updateJson({ buttons });
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

.rc-hero-title :deep(:is(p, h2)) {
  margin: 0;
  font: inherit;
  color: inherit;
  text-align: inherit;
  text-transform: uppercase;
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
  color: inherit;
  line-height: inherit;
}

.rc-hero-description-editor :deep(.ProseMirror p) {
  margin: 0;
}
</style>
