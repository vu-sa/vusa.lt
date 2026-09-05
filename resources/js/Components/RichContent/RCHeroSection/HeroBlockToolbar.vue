<template>
  <RCBlockToolbarShell
    :content :block-key="blockKey" :reference
    :can-move-up="canMoveUp" :can-move-down="canMoveDown" :can-delete="canDelete"
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <Field>
        <FieldLabel>{{ $t('rich-content.hero_variant') }}</FieldLabel>
        <div class="grid grid-cols-4 gap-1.5">
          <button v-for="variantOption in variantOptions" :key="variantOption.value" type="button"
            class="group relative overflow-visible rounded-lg border-2 p-1.5 text-left transition-all duration-200"
            :class="[
              variant === variantOption.value
                ? 'border-vusa-red bg-red-50/50 ring-2 ring-vusa-red/20 dark:bg-red-950/20'
                : 'border-border hover:border-zinc-300 dark:hover:border-zinc-600'
            ]" @click="setVariant(variantOption.value)">
            <div class="absolute -right-1 -top-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-vusa-red text-white shadow-md transition-all"
              :class="variant === variantOption.value ? 'scale-100 opacity-100' : 'scale-75 opacity-0'">
              <IFluentCheckmark12Regular class="h-2 w-2" />
            </div>
            <div class="flex justify-center transition-opacity"
              :class="variant === variantOption.value ? 'opacity-100' : 'opacity-50 group-hover:opacity-75'">
              <component :is="variantOption.icon" class="h-8 w-14" />
            </div>
          </button>
        </div>
      </Field>

      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths="allowedWidths" @update:model-value="setWidth" />
      </div>

      <RCPresentationPicker
        v-if="variant !== 'panel'"
        :model-value="presentation"
        :plain-padding="plainPadding"
        :disabled="presentationDisabled"
        @update:model-value="setPresentation"
        @update:plain-padding="setPlainPadding"
      />
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
/**
 * Hero's whole-block toolbar: `RCBlockToolbarShell`'s generic chrome plus the variant
 * picker (markup lifted verbatim from `HeroForm.vue`), `RCWidthPicker`, and
 * `RCPresentationPicker` (hidden for `panel`, which ignores presentation entirely — same
 * gate `HeroForm.vue` already uses).
 */
import { computed, h } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCPresentationPicker from '../Editor/RCPresentationPicker.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import type { BlockPresentation } from '../bandLayout';
import type { PlainPadding } from '../sectionClasses';
import type { Hero } from '@/Types/contentParts';

import { Field, FieldLabel } from '@/Components/ui/field';
import IFluentCheckmark12Regular from '~icons/fluent/checkmark12-regular';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
  presentationDisabled?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const heroOptions = computed(() => (props.content.options ?? {}) as Hero['options']);
const variant = computed<NonNullable<Hero['options']['variant']>>(() => heroOptions.value.variant ?? 'split');
const presentation = computed<BlockPresentation | undefined>(() => heroOptions.value.presentation);
const plainPadding = computed<PlainPadding | undefined>(() => heroOptions.value.plainPadding);

const contentType = computed(() => getContentType('hero'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (heroOptions.value.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function setVariant(value: NonNullable<Hero['options']['variant']>): void {
  emit('update:content', { ...props.content, options: { ...heroOptions.value, variant: value } });
}

function setPresentation(value: BlockPresentation): void {
  emit('update:content', { ...props.content, options: { ...heroOptions.value, presentation: value } });
}

function setPlainPadding(value: PlainPadding): void {
  emit('update:content', { ...props.content, options: { ...heroOptions.value, plainPadding: value } });
}

// Variant skeleton icons — same simple SVG schematics as HeroForm.vue's picker, scaled
// down for the toolbar's compact popover.
const SplitVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 6, y: 12, width: 34, height: 8, rx: 2 }),
  h('rect', { x: 6, y: 26, width: 34, height: 14, rx: 2 }),
  h('rect', { x: 6, y: 46, width: 16, height: 8, rx: 2 }),
  h('rect', { x: 50, y: 8, width: 40, height: 48, rx: 3 }),
]);

const CenteredVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 28, y: 12, width: 40, height: 8, rx: 2 }),
  h('rect', { x: 20, y: 26, width: 56, height: 10, rx: 2 }),
  h('rect', { x: 38, y: 42, width: 20, height: 8, rx: 2 }),
]);

const BannerVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 4, y: 26, width: 60, height: 12, rx: 2 }),
  h('rect', { x: 72, y: 26, width: 20, height: 12, rx: 2 }),
]);

const PanelVariantIcon = () => h('svg', { viewBox: '0 0 96 64', fill: 'none', stroke: 'currentColor', strokeWidth: 2 }, [
  h('rect', { x: 2, y: 2, width: 92, height: 60, rx: 8 }),
  h('rect', { x: 10, y: 16, width: 32, height: 32, rx: 6 }),
  h('rect', { x: 50, y: 18, width: 36, height: 8, rx: 2 }),
  h('rect', { x: 50, y: 30, width: 36, height: 14, rx: 2 }),
]);

const variantOptions: { value: NonNullable<Hero['options']['variant']>; icon: unknown }[] = [
  { value: 'split', icon: SplitVariantIcon },
  { value: 'centered', icon: CenteredVariantIcon },
  { value: 'banner', icon: BannerVariantIcon },
  { value: 'panel', icon: PanelVariantIcon },
];
</script>
