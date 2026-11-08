<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @update:content="$emit('update:content', $event)"
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
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <RCPresentationPicker
        v-if="variant !== 'panel'"
        :model-value="presentation"
        :plain-padding
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
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { Hero } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCPresentationPicker from '../Editor/RCPresentationPicker.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import type { BlockPresentation } from '../bandLayout';
import type { PlainPadding } from '../sectionClasses';

import { BannerVariantIcon, CenteredVariantIcon, PanelVariantIcon, SplitVariantIcon } from './heroVariantIcons';

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

const variantOptions: { value: NonNullable<Hero['options']['variant']>; icon: unknown }[] = [
  { value: 'split', icon: SplitVariantIcon },
  { value: 'centered', icon: CenteredVariantIcon },
  { value: 'banner', icon: BannerVariantIcon },
  { value: 'panel', icon: PanelVariantIcon },
];
</script>
