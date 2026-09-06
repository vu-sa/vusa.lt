<template>
  <Popover :open="hotspots.isPopoverOpen(hotspotId)" @update:open="onOpenChange">
    <PopoverAnchor :reference="triggerRef ?? undefined" />
    <button
      ref="triggerRef" type="button"
      :class="[
        'absolute left-2 top-2 z-30 flex size-7 items-center justify-center rounded-md',
        'border border-border bg-background/90 text-muted-foreground shadow-sm backdrop-blur-sm',
        'transition-colors hover:bg-secondary',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/50',
      ]"
      data-rc-interactive data-rc-grid-column-options
      :aria-label="$t('rich-content.grid_column_settings')" :title="$t('rich-content.grid_column_settings')"
      @click.stop="hotspots.openPopover(hotspotId)"
      @keydown.stop
    >
      <IFluentOptions24Regular class="size-3.5" />
    </button>
    <PopoverContent v-if="hotspots.isPopoverOpen(hotspotId)" data-surface="public"
      class="w-[min(20rem,calc(100vw-2rem))]"
      @close-auto-focus.prevent
    >
      <div class="flex flex-col gap-4">
        <Field>
          <FieldLabel>{{ $t('rich-content.grid_column_type') }}</FieldLabel>
          <Select :model-value="type" @update:model-value="$emit('update:type', $event as string)">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="tiptap">
                {{ $t('rich-content.grid_column_type_text') }}
              </SelectItem>
              <SelectItem value="image">
                {{ $t('rich-content.image') }}
              </SelectItem>
              <SelectItem value="card">
                {{ $t('rich-content.grid_column_type_card') }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>
        <Field class="border-t border-border pt-4">
          <FieldLabel>{{ $t('rich-content.grid_column_width') }}</FieldLabel>
          <Select :model-value="width" @update:model-value="$emit('update:width', $event as string)">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in COLUMN_WIDTH_OPTIONS" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </Field>
        <div v-if="canMoveLeft || canMoveRight" class="flex items-center gap-2 border-t border-border pt-4">
          <Button type="button" variant="outline" size="sm" :disabled="!canMoveLeft" @click="$emit('move-left')">
            <IFluentArrowLeft24Regular class="mr-1 size-4" />
            {{ $t('rich-content.move_left') }}
          </Button>
          <Button type="button" variant="outline" size="sm" :disabled="!canMoveRight" @click="$emit('move-right')">
            <IFluentArrowRight24Regular class="mr-1 size-4" />
            {{ $t('rich-content.move_right') }}
          </Button>
        </div>
        <div class="border-t border-border pt-4">
          <Button type="button" variant="destructive" size="sm" :disabled="!canRemove" @click="$emit('remove')">
            <IFluentDelete24Regular class="mr-1 size-4" />
            {{ $t('rich-content.grid_remove_column') }}
          </Button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
/**
 * Per-column settings popover for content-grid — the structural counterpart to the
 * type-specific editing already on the cell (live tiptap text, RCImageHotspot, card
 * inline fields). Every column gets exactly one of these, at a fixed corner, regardless
 * of content type: this is "the column's own popover", not folded into the block-level
 * More Options toolbar. See RICH_CONTENT_EDITOR.md.
 */
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';

import { Button } from '@/Components/ui/button';
import { Field, FieldLabel } from '@/Components/ui/field';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import IFluentArrowLeft24Regular from '~icons/fluent/arrow-left24-regular';
import IFluentArrowRight24Regular from '~icons/fluent/arrow-right24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentOptions24Regular from '~icons/fluent/options24-regular';

const COLUMN_WIDTH_OPTIONS: { label: string; value: string }[] = [
  { label: '25%', value: 'col-span-3' },
  { label: '33%', value: 'col-span-4' },
  { label: '40%', value: 'col-span-5' },
  { label: '50%', value: 'col-span-6' },
  { label: '60%', value: 'col-span-7' },
  { label: '66%', value: 'col-span-8' },
  { label: '75%', value: 'col-span-9' },
  { label: '100%', value: 'col-span-12' },
];

const props = defineProps<{
  blockKey: string;
  rowIndex: number;
  colIndex: number;
  type: string;
  width: string;
  canMoveLeft: boolean;
  canMoveRight: boolean;
  canRemove: boolean;
}>();

defineEmits<{
  (e: 'update:type', value: string): void;
  (e: 'update:width', value: string): void;
  (e: 'move-left'): void;
  (e: 'move-right'): void;
  (e: 'remove'): void;
}>();

const hotspots = injectActiveHotspot();
const hotspotId = computed(() => `${props.blockKey}:col-${props.rowIndex}-${props.colIndex}`);
const triggerRef = ref<HTMLElement | null>(null);

function onOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(hotspotId.value);
  else hotspots.close(hotspotId.value);
}
</script>
