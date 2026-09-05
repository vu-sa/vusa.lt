<template>
  <RCBlockToolbarShell
    :content :block-key :reference
    :can-move-up :can-move-down :can-delete
    @move-up="$emit('move-up')"
    @move-down="$emit('move-down')"
    @delete="$emit('delete')"
    @open-form="$emit('open-form')"
  >
    <div class="flex flex-col gap-3">
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between gap-2">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <div class="grid grid-cols-2 gap-3">
        <Field>
          <FieldLabel>{{ $t('rich-content.section_heading_level') }}</FieldLabel>
          <Select :model-value="String(headingLevel)" @update:model-value="setHeadingLevel(Number($event) as 2 | 3 | 4)">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="2">
                {{ $t('rich-content.heading_level_2') }}
              </SelectItem>
              <SelectItem value="3">
                {{ $t('rich-content.heading_level_3') }}
              </SelectItem>
              <SelectItem value="4">
                {{ $t('rich-content.heading_level_4') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>
        <Field>
          <FieldLabel>{{ $t('rich-content.section_align') }}</FieldLabel>
          <Select :model-value="align" @update:model-value="setAlign($event as 'center' | 'start')">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="center">
                {{ $t('rich-content.section_align_center') }}
              </SelectItem>
              <SelectItem value="start">
                {{ $t('rich-content.section_align_start') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>
      </div>

      <div class="flex items-center justify-between">
        <FieldLabel class="mb-0">
          {{ $t('rich-content.section_show_separator') }}
        </FieldLabel>
        <Switch :model-value="showSeparator" @update:model-value="setShowSeparator" />
      </div>

      <RCPresentationPicker
        :model-value="presentation"
        :plain-padding
        @update:model-value="setPresentation"
        @update:plain-padding="setPlainPadding"
      />
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
/**
 * Section's whole-block toolbar: `RCBlockToolbarShell`'s generic chrome plus the
 * width/heading-level/alignment/separator controls and `RCPresentationPicker` — the
 * same quick-access-while-editing shape `HeroBlockToolbar` gives Hero's variant picker.
 * `align` is deliberately one setting for the whole header (eyebrow + title + subtitle
 * together, see SectionHeader.vue), not a per-field control.
 *
 * Width is re-implemented here (not left to the generic branch) for the same reason
 * Hero re-implements it: this type routes to its own toolbar instead of
 * `RCBlockToolbarShell`'s default slot.
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCPresentationPicker from '../Editor/RCPresentationPicker.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import type { BlockPresentation } from '../bandLayout';
import type { PlainPadding, SectionHeadingLevel } from '../sectionClasses';

import type { Section } from '@/Types/contentParts';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:content', value: ContentPart): void;
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const sectionOptions = computed(() => (props.content.options ?? {}) as Section['options']);
const headingLevel = computed<SectionHeadingLevel>(() => sectionOptions.value.headingLevel ?? 2);
const align = computed<NonNullable<Section['options']['align']>>(() => sectionOptions.value.align ?? 'center');
const showSeparator = computed(() => sectionOptions.value.showSeparator ?? true);
const presentation = computed<BlockPresentation | undefined>(() => sectionOptions.value.presentation);
const plainPadding = computed<PlainPadding | undefined>(() => sectionOptions.value.plainPadding);

const contentType = computed(() => getContentType('section'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (sectionOptions.value.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

function patchOptions(patch: Partial<Section['options']>): void {
  emit('update:content', { ...props.content, options: { ...sectionOptions.value, ...patch } });
}

function setHeadingLevel(value: SectionHeadingLevel): void {
  patchOptions({ headingLevel: value });
}

function setAlign(value: NonNullable<Section['options']['align']>): void {
  patchOptions({ align: value });
}

function setShowSeparator(value: boolean): void {
  patchOptions({ showSeparator: value });
}

function setPresentation(value: BlockPresentation): void {
  patchOptions({ presentation: value });
}

function setPlainPadding(value: PlainPadding): void {
  patchOptions({ plainPadding: value });
}
</script>
