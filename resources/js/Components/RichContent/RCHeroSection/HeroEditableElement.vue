<template>
  <HeroElement :element :is-first-element="false" :band>
    <template #eyebrow>
      <EyebrowLabel>
        <RCInlineText
          as="span" :model-value="element.json_content.eyebrow ?? ''" editable
          :placeholder="$t('rich-content.eyebrow')" @update:model-value="updateJson({ eyebrow: $event })"
        />
      </EyebrowLabel>
    </template>

    <template #title>
      <div v-if="!isTitleLive" role="heading" :aria-level="variant === 'banner' ? 2 : 1" :class="[titleClass, titleAlignmentClass]">
        <button type="button" class="block w-full cursor-text text-inherit" data-rc-interactive @click="claimTitle">
          <span v-if="hasTitle" v-html="element.json_content.title" />
          <span v-else class="text-muted-foreground/50">{{ $t('rich-content.title') }}</span>
        </button>
      </div>
      <div v-else :class="[titleClass, titleAlignmentClass]" data-rc-interactive @focusout="releaseTitle">
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
    </template>

    <template #description>
      <p v-if="!isDescriptionLive" :class="descriptionClass">
        <button type="button" class="block w-full cursor-text text-left" data-rc-interactive @click="claimDescription">
          <span v-if="hasDescription" v-html="element.json_content.description" />
          <span v-else class="text-muted-foreground/50">{{ $t('rich-content.description') }}</span>
        </button>
      </p>
      <TiptapEditor
        v-else
        :model-value="element.json_content.description ?? ''" preset="marks" toolbar="bubble" html
        :class="['rc-hero-description-editor', descriptionClass]"
        :placeholder="$t('rich-content.description')" data-rc-interactive
        @focusout="releaseDescription" @update:model-value="updateDescription"
      />
    </template>

    <template #buttons>
      <HeroButtonsEditable :buttons="element.json_content.buttons" :block-key="blockKey" :variant :class="buttonsClass" @update:buttons="updateButtons" />
    </template>

    <template #image>
      <HeroImageHotspot :content="element" :block-key="blockKey" @update:content="$emit('update:element', $event)" />
    </template>
  </HeroElement>
</template>

<script setup lang="ts">
/**
 * Full-screen-editor replacement for `HeroElement.vue`'s author-editable spots. Wraps the
 * pure display and overrides only its `eyebrow`/`title`/`description`/`buttons`/`image`
 * slots, so structure/classes/variants stay owned by `HeroElement.vue` (imported here, not
 * duplicated) and this file owns only hotspot injection, update handlers, and the editing
 * controls themselves (TipTap, popovers). Only ever mounted while the block is being
 * edited (see `Types/index.ts`'s `editableDisplay` / `BlockPreviewRenderer.vue`), so
 * `injectActiveHotspot()` can assume `RCFullscreenEditor.vue`'s provider is present.
 */
import { computed, defineAsyncComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import HeroElement from './HeroElement.vue';
import HeroButtonsEditable from './HeroButtonsEditable.vue';
import HeroImageHotspot from './HeroImageHotspot.vue';
import RCInlineText from '../Editor/Fullscreen/RCInlineText.vue';
import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';
import { EyebrowLabel } from '@/Components/Public/Base';
import type { Hero } from '@/Types/contentParts';
import { hasHtmlText } from '@/Utils/String';
import type { BandResolution } from '../bandLayout';
import { heroButtonsClass, heroDescriptionClass, heroTitleAlignmentClass, heroTitleClass } from './heroLayout';

// Lazy-loaded: title/description only ever mount a *live* TiptapEditor while claimed.
const TiptapEditor = defineAsyncComponent(() => import('@/Components/TipTap/TiptapEditor.vue'));

const props = defineProps<{
  element: Hero;
  band?: BandResolution;
  /** This block's identity for hotspot ids (`${blockKey}:title`, `:image`, `:buttons:N`). */
  blockKey?: string;
  /** BlockPreviewRenderer passes these to every display/editableDisplay uniformly; neither
   *  is meaningful here (this component is never "first" on the real page, and Hero has no
   *  server-resolved HTML) — declared only to keep them out of fallthrough attrs. */
  isFirstElement?: boolean;
  html?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:element', value: Hero): void;
}>();

defineOptions({ inheritAttrs: false });

const hotspots = injectActiveHotspot();

const variant = computed(() => props.element.options?.variant ?? 'split');
const titleClass = computed(() => heroTitleClass(variant.value));
const titleAlignmentClass = computed(() => heroTitleAlignmentClass(variant.value));
const descriptionClass = computed(() => heroDescriptionClass(variant.value));
const buttonsClass = computed(() => heroButtonsClass(variant.value));

const blockKey = computed(() => props.blockKey ?? '');
const titleHotspotId = computed(() => `${blockKey.value}:title`);
const descriptionHotspotId = computed(() => `${blockKey.value}:description`);
const hasTitle = computed(() => hasHtmlText(props.element.json_content.title));
const hasDescription = computed(() => hasHtmlText(props.element.json_content.description));
const isTitleLive = computed(() => hotspots.isTextFieldLive(titleHotspotId.value));
const isDescriptionLive = computed(() => hotspots.isTextFieldLive(descriptionHotspotId.value));

function claimTitle(): void {
  hotspots.openTextField(titleHotspotId.value);
}

function releaseTitle(): void {
  hotspots.close(titleHotspotId.value);
}

function updateTitle(value: string): void {
  updateJson({ title: value });
}

function claimDescription(): void {
  hotspots.openTextField(descriptionHotspotId.value);
}

function releaseDescription(): void {
  hotspots.close(descriptionHotspotId.value);
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
/* Native <button> resets text-transform to `none` in the browser's UA stylesheet —
   without this, the static/clickable title (before it's claimed into the live
   TipTap editor below) loses the "uppercase" its wrapping .rc-hero-title div carries.
   Scoped to this file, not HeroElement.vue: this button/span markup is this
   component's own #title slot override, compiled under its own scope hash. */
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
