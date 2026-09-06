<template>
  <div class="group/rc-block relative">
    <div ref="rootRef" class="rc-fullscreen-block-display">
      <BlockPreviewRenderer
        :element="content"
        :resolved
        :band
        editable
        :preview
        :block-key
        :active-inline-field
        @update:element="$emit('update:content', $event)"
        @claim-inline-field="onClaimInlineField"
      />
    </div>

    <HeroBlockToolbar v-if="!preview && content.type === 'hero'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <SectionBlockToolbar v-else-if="!preview && content.type === 'section'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <LinkListBlockToolbar v-else-if="!preview && content.type === 'link-list'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <EventListBlockToolbar v-else-if="!preview && content.type === 'event-list'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <CalendarBlockToolbar v-else-if="!preview && content.type === 'calendar'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <NewsBlockToolbar v-else-if="!preview && content.type === 'news'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <InstitutionListBlockToolbar v-else-if="!preview && content.type === 'institution-list'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <HeroCarouselBlockToolbar v-else-if="!preview && content.type === 'hero-carousel'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    />
    <CardBlockToolbar v-else-if="!preview && content.type === 'shadcn-card'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')" @move-down="$emit('move-down')"
      @delete="$emit('delete')" @open-form="$emit('open-form')"
    />
    <NumberStatBlockToolbar v-else-if="!preview && content.type === 'number-stat-section'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')" @move-down="$emit('move-down')"
      @delete="$emit('delete')" @open-form="$emit('open-form')"
    />
    <FlowGraphBlockToolbar v-else-if="!preview && content.type === 'flow-graph'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')" @move-down="$emit('move-down')"
      @delete="$emit('delete')" @open-form="$emit('open-form')"
    />
    <RCImageListBlockToolbar v-else-if="!preview && (content.type === 'image-grid' || content.type === 'photo-gallery')"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')" @move-down="$emit('move-down')"
      @delete="$emit('delete')" @open-form="$emit('open-form')"
    />
    <CardStackBlockToolbar v-else-if="!preview && content.type === 'card-stack'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')" @move-down="$emit('move-down')"
      @delete="$emit('delete')" @open-form="$emit('open-form')"
    />
    <CarouselSlideDeckBlockToolbar v-else-if="!preview && content.type === 'carousel-slide-deck'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')" @move-down="$emit('move-down')"
      @delete="$emit('delete')" @open-form="$emit('open-form')"
    />
    <ProcessStepsBlockToolbar v-else-if="!preview && content.type === 'process-steps'"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete :presentation-disabled="band?.isSectionChild"
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')" @move-down="$emit('move-down')"
      @delete="$emit('delete')" @open-form="$emit('open-form')"
    />
    <RCBlockToolbarShell v-else-if="!preview"
      :content :block-key :reference="rootRef"
      :can-move-up :can-move-down :can-delete
      @update:content="$emit('update:content', $event)"
      @move-up="$emit('move-up')"
      @move-down="$emit('move-down')"
      @delete="$emit('delete')"
      @open-form="$emit('open-form')"
    >
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>
      <RCPresentationPicker
        v-if="isBand && !contentType.usesSectionChrome"
        :model-value="presentation"
        :plain-padding
        :disabled="band?.isSectionChild"
        @update:model-value="setPresentation"
        @update:plain-padding="setPlainPadding"
      />
      <RCSectionToolbarOptions
        v-if="contentType.usesSectionChrome"
        :model-value="sectionOptions"
        :presentation-disabled="band?.isSectionChild"
        @update:model-value="setSectionOptions"
      />
    </RCBlockToolbarShell>
  </div>
</template>

<script setup lang="ts">
/**
 * Per-block wrapper for the full-screen editor: the real rendered output
 * (`BlockPreviewRenderer`, same component forms-mode preview and the picker use) plus
 * the whole-block toolbar — a dedicated type toolbar (`HeroBlockToolbar`, `SectionBlockToolbar`,
 * `LinkListBlockToolbar`, `EventListBlockToolbar`, `CalendarBlockToolbar`) for a type whose
 * structured fields have no on-canvas representation to click into, the generic
 * `RCBlockToolbarShell` for every other type — see its own docblock for why every type
 * gets this on day one. No selection state, no pointer-events CSS lockdown — this design
 * has no "select the block" concept at all.
 *
 * Bridges a display's `activeInlineField`/`claim-inline-field` contract onto the shared
 * `useActiveHotspot` state as a `kind: 'text'` claim. This keeps a card's live body
 * editor (and any future rich inline field) in the same single-live-editor invariant as
 * Hero's hotspots without `BlockPreviewRenderer.vue` needing to know the composable.
 */
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import BlockPreviewRenderer from '../BlockPreviewRenderer.vue';
import RCPresentationPicker from '../RCPresentationPicker.vue';
import RCWidthPicker from '../RCWidthPicker.vue';
import { withWidth } from '../blockWidth';
import HeroBlockToolbar from '../../RCHeroSection/HeroBlockToolbar.vue';
import SectionBlockToolbar from '../../RCSection/SectionBlockToolbar.vue';
import LinkListBlockToolbar from '../../RCLinkList/LinkListBlockToolbar.vue';
import EventListBlockToolbar from '../../RCEventList/EventListBlockToolbar.vue';
import CalendarBlockToolbar from '../../RCCalendar/CalendarBlockToolbar.vue';
import NewsBlockToolbar from '../../RCNews/NewsBlockToolbar.vue';
import InstitutionListBlockToolbar from '../../RCInstitutionList/InstitutionListBlockToolbar.vue';
import HeroCarouselBlockToolbar from '../../RCHeroCarousel/HeroCarouselBlockToolbar.vue';
import CardBlockToolbar from '../../RCCard/CardBlockToolbar.vue';
import NumberStatBlockToolbar from '../../RCNumberStatSection/NumberStatBlockToolbar.vue';
import FlowGraphBlockToolbar from '../../RCFlowGraph/FlowGraphBlockToolbar.vue';
import CardStackBlockToolbar from '../../RCCardStack/CardStackBlockToolbar.vue';
import CarouselSlideDeckBlockToolbar from '../../RCCarouselSlideDeck/CarouselSlideDeckBlockToolbar.vue';
import ProcessStepsBlockToolbar from '../../RCProcessSteps/ProcessStepsBlockToolbar.vue';
import { getContentType, type BlockWidth, type ContentPart } from '../../Types';
import { resolveBandRole, type BandResolution, type BlockPresentation } from '../../bandLayout';
import type { PlainPadding } from '../../sectionClasses';
import RCSectionToolbarOptions from '../RCSectionToolbarOptions.vue';

import RCImageListBlockToolbar from './RCImageListBlockToolbar.vue';
import { injectActiveHotspot } from './useActiveHotspot';
import RCBlockToolbarShell from './RCBlockToolbarShell.vue';

import type { SectionOptions } from '@/Types/contentParts';
import { FieldLabel } from '@/Components/ui/field';

const props = defineProps<{
  content: ContentPart;
  resolved?: unknown;
  band?: BandResolution;
  blockKey: string;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
  preview?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const rootRef = ref<HTMLElement | null>(null);
const hotspots = injectActiveHotspot();

const activeInlineField = computed<string | null>(() => (hotspots.active.value?.kind === 'text' ? hotspots.active.value.id : null));

function onClaimInlineField(field: string | null): void {
  if (field) hotspots.openTextField(field);
  else hotspots.close();
}

const contentType = computed(() => getContentType(props.content.type));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (props.content.options?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);
const isBand = computed(() => resolveBandRole(props.content.type, props.content.options) === 'band');
const presentation = computed<BlockPresentation | undefined>(() => props.content.options?.presentation as BlockPresentation | undefined);
const plainPadding = computed<PlainPadding | undefined>(() => props.content.options?.plainPadding as PlainPadding | undefined);

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function setPresentation(value: BlockPresentation): void {
  emit('update:content', { ...props.content, options: { ...(props.content.options ?? {}), presentation: value } });
}

function setPlainPadding(value: PlainPadding): void {
  emit('update:content', { ...props.content, options: { ...(props.content.options ?? {}), plainPadding: value } });
}

const sectionOptions = computed<SectionOptions>(() => (props.content.options ?? {}) as SectionOptions);

function setSectionOptions(value: SectionOptions): void {
  emit('update:content', { ...props.content, options: { ...(props.content.options ?? {}), ...value } });
}
</script>
