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
      <!-- Grid-wide layout options — row/column structure and per-cell content type/width
           live on the canvas now (RCGridRowOptions / RCGridColumnOptions), not here. -->
      <div class="flex flex-col gap-2.5">
        <div class="flex items-center justify-between gap-2">
          <FieldLabel>{{ $t('rich-content.grid_gap') }}</FieldLabel>
          <Select :model-value="gap" @update:model-value="setGap($event as string)">
            <SelectTrigger class="h-8 w-40 text-xs">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in gapOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="flex items-center justify-between gap-2">
          <FieldLabel>{{ $t('rich-content.grid_mobile_stacking') }}</FieldLabel>
          <div class="flex items-center gap-2">
            <span class="text-xs text-muted-foreground">{{ $t('rich-content.grid_mobile_stacking_on') }}</span>
            <Switch :model-value="mobileStacking" @update:model-value="setMobileStacking" />
          </div>
        </div>
        <div class="flex items-center justify-between gap-2">
          <FieldLabel>{{ $t('rich-content.grid_equal_height') }}</FieldLabel>
          <div class="flex items-center gap-2">
            <span class="text-xs text-muted-foreground">{{ $t('rich-content.grid_equal_height_on') }}</span>
            <Switch :model-value="equalHeight" @update:model-value="setEqualHeight" />
          </div>
        </div>
        <div class="flex items-center justify-between gap-2">
          <FieldLabel>{{ $t('rich-content.grid_dividers') }}</FieldLabel>
          <div class="flex items-center gap-2">
            <span class="text-xs text-muted-foreground">{{ $t('rich-content.grid_dividers_on') }}</span>
            <Switch :model-value="dividers" @update:model-value="setDividers" />
          </div>
        </div>
        <div class="flex items-center justify-between gap-2">
          <FieldLabel>{{ $t('rich-content.grid_vertical_align') }}</FieldLabel>
          <Select :model-value="verticalAlign" @update:model-value="setVerticalAlign($event as string)">
            <SelectTrigger class="h-8 w-32 text-xs">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="stretch">
                {{ $t('rich-content.grid_vertical_align_stretch') }}
              </SelectItem>
              <SelectItem value="start">
                {{ $t('rich-content.grid_vertical_align_start') }}
              </SelectItem>
              <SelectItem value="center">
                {{ $t('rich-content.grid_vertical_align_center') }}
              </SelectItem>
              <SelectItem value="end">
                {{ $t('rich-content.grid_vertical_align_end') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Width picker -->
      <div v-if="allowedWidths.length > 1" class="flex items-center justify-between border-t border-border pt-3">
        <FieldLabel>{{ $t('rich-content.width') }}</FieldLabel>
        <RCWidthPicker :model-value="currentWidth" :allowed-widths @update:model-value="setWidth" />
      </div>

      <RCSectionToolbarOptions v-model="sectionOptions" :presentation-disabled />
    </div>
  </RCBlockToolbarShell>
</template>

<script setup lang="ts">
/**
 * Block-wide content-grid settings only: layout options (gap, stacking, equal height,
 * dividers, vertical align), width, and section chrome. Row/column structure — add,
 * remove, reorder, per-column content type and width — lives on the canvas instead,
 * via RCGridRowOptions and RCGridColumnOptions anchored to each row/column. See
 * RICH_CONTENT_EDITOR.md.
 */
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCBlockToolbarShell from '../Editor/Fullscreen/RCBlockToolbarShell.vue';
import RCSectionToolbarOptions from '../Editor/RCSectionToolbarOptions.vue';
import RCWidthPicker from '../Editor/RCWidthPicker.vue';
import { withWidth } from '../Editor/blockWidth';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';

import type { ContentGrid, SectionOptions } from '@/Types/contentParts';
import { FieldLabel } from '@/Components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

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

const gapOptions = computed(() => [
  { label: $t('rich-content.grid_gap_sm'), value: 'gap-2' },
  { label: $t('rich-content.grid_gap_md'), value: 'gap-4' },
  { label: $t('rich-content.grid_gap_lg'), value: 'gap-6' },
  { label: $t('rich-content.grid_gap_xl'), value: 'gap-8' },
]);

const options = computed<ContentGrid['options']>(() => (props.content.options ?? {}) as ContentGrid['options']);

const gap = computed(() => options.value.gap ?? 'gap-4');
const mobileStacking = computed(() => options.value.mobileStacking ?? true);
const equalHeight = computed(() => options.value.equalHeight ?? false);
const dividers = computed(() => options.value.dividers ?? false);
const verticalAlign = computed(() => options.value.verticalAlign ?? 'stretch');

function patchOptions(patch: Partial<NonNullable<ContentGrid['options']>>): void {
  emit('update:content', { ...props.content, options: { ...options.value, ...patch } });
}

function setGap(value: string): void {
  patchOptions({ gap: value as NonNullable<ContentGrid['options']>['gap'] });
}

function setMobileStacking(value: boolean): void {
  patchOptions({ mobileStacking: value });
}

function setEqualHeight(value: boolean): void {
  patchOptions({ equalHeight: value });
}

function setDividers(value: boolean): void {
  patchOptions({ dividers: value });
}

function setVerticalAlign(value: string): void {
  patchOptions({ verticalAlign: value as NonNullable<ContentGrid['options']>['verticalAlign'] });
}

const contentType = computed(() => getContentType('content-grid'));
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (options.value.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function setWidth(width: BlockWidth): void {
  emit('update:content', withWidth(props.content, width));
}

const sectionOptions = computed<SectionOptions>({
  get: () => options.value as SectionOptions,
  set: value => emit('update:content', { ...props.content, options: { ...options.value, ...value } }),
});
</script>
