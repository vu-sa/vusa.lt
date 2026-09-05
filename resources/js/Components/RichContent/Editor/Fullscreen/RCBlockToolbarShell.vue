<template>
  <div
    class="pointer-events-auto absolute top-6 right-3 z-30 opacity-100"
  >
    <Popover :open="hotspots.isPopoverOpen(toolbarId)" @update:open="onOpenChange">
      <PopoverAnchor :reference="toolbarButtonRef ?? reference ?? undefined" />
      <button
        ref="toolbarButtonRef"
        type="button"
        class="flex size-7 items-center justify-center rounded-md border border-zinc-200 bg-white text-zinc-500 shadow-sm transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
        data-rc-interactive
        :title="$t('rich-content.block_settings')"
        @click="hotspots.openPopover(toolbarId)"
      >
        <IFluentMoreVertical24Regular class="size-3.5" />
      </button>
      <PopoverContent
        v-if="hotspots.isPopoverOpen(toolbarId)"
        data-surface="public"
        align="end"
        class="w-[min(30rem,calc(100vw-2rem))]"
        @close-auto-focus.prevent
      >
        <div class="flex flex-col gap-3">
          <div class="flex items-center justify-between gap-1 border-b border-border pb-2">
            <div class="flex items-center gap-0.5">
              <button type="button" class="flex size-7 items-center justify-center rounded text-zinc-500 transition-colors hover:bg-zinc-100 disabled:opacity-30 dark:hover:bg-zinc-800"
                :disabled="!canMoveUp" :title="$t('rich-content.move_up')" @click="$emit('move-up')">
                <IFluentArrowUp24Regular class="size-3.5" />
              </button>
              <button type="button" class="flex size-7 items-center justify-center rounded text-zinc-500 transition-colors hover:bg-zinc-100 disabled:opacity-30 dark:hover:bg-zinc-800"
                :disabled="!canMoveDown" :title="$t('rich-content.move_down')" @click="$emit('move-down')">
                <IFluentArrowDown24Regular class="size-3.5" />
              </button>
            </div>
            <div class="flex items-center gap-0.5">
              <button type="button" class="flex size-7 items-center justify-center rounded text-zinc-500 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
                :title="$t('rich-content.side_by_side')" @click="$emit('open-form')">
                <IFluentLayoutColumnTwoSplitLeft24Regular class="size-3.5" />
              </button>
              <button type="button" class="flex size-7 items-center justify-center rounded text-zinc-500 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-zinc-500 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                :disabled="!canDelete" :title="$t('rich-content.delete_block')" @click="$emit('delete')">
                <IFluentDismiss24Regular class="size-3.5" />
              </button>
            </div>
          </div>

          <slot />
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
/**
 * Generic whole-block chrome for the full-screen editor: move/delete/"open full form",
 * plus a default slot for type-specific controls (width/presentation/variant — not
 * hardcoded here, not every type wants all three). Every block type gets this in
 * full-screen mode; only a migrated type (Hero) *additionally* gets field-level hotspots.
 *
 * Anchored to its visible trigger. `reference` is retained as a fallback while Vue mounts
 * the button, so a popover never loses its anchor during the first render.
 */
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { ContentPart } from '../../Types';

import { injectActiveHotspot } from './useActiveHotspot';

import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import IFluentMoreVertical24Regular from '~icons/fluent/more-vertical24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentLayoutColumnTwoSplitLeft24Regular from '~icons/fluent/layout-column-two-split-left24-regular';
import IFluentDismiss24Regular from '~icons/fluent/dismiss24-regular';

const props = defineProps<{
  content: ContentPart;
  blockKey: string;
  /** The block's rendered root element — anchors the popover to the block's own
   *  bounding box rather than to this (invisible-until-hover) trigger button. */
  reference?: Element | null;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canDelete: boolean;
}>();

defineEmits<{
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'delete'): void;
  (e: 'open-form'): void;
}>();

const hotspots = injectActiveHotspot();
const toolbarId = computed(() => `${props.blockKey}:toolbar`);
const toolbarButtonRef = ref<HTMLElement | null>(null);

function onOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(toolbarId.value);
  else hotspots.close(toolbarId.value);
}
</script>
