<template>
  <Popover :open="hotspots.isPopoverOpen(hotspotId)" @update:open="onOpenChange">
    <PopoverAnchor :reference="triggerRef ?? undefined" />
    <button
      ref="triggerRef" type="button"
      :class="[
        'flex items-center gap-1 rounded-md border border-border bg-background/90 px-2 py-1',
        'text-xs font-medium text-muted-foreground shadow-sm backdrop-blur-sm transition-colors hover:bg-secondary',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/50',
      ]"
      data-rc-interactive data-rc-grid-row-options
      :aria-label="$t('rich-content.grid_row_settings', { n: String(rowIndex + 1) })"
      :title="$t('rich-content.grid_row_settings', { n: String(rowIndex + 1) })"
      @click.stop="hotspots.openPopover(hotspotId)"
      @keydown.stop
    >
      {{ $t('rich-content.grid_row') }} {{ rowIndex + 1 }}
      <IFluentMoreVertical24Regular class="size-3" />
    </button>
    <PopoverContent v-if="hotspots.isPopoverOpen(hotspotId)" data-surface="public"
      class="w-64"
      @close-auto-focus.prevent
    >
      <div class="flex flex-col gap-3">
        <div v-if="canMoveUp || canMoveDown" class="flex items-center gap-2">
          <Button type="button" variant="outline" size="sm" :disabled="!canMoveUp" @click="$emit('move-up')">
            <IFluentArrowUp24Regular class="mr-1 size-4" />
            {{ $t('rich-content.move_up') }}
          </Button>
          <Button type="button" variant="outline" size="sm" :disabled="!canMoveDown" @click="$emit('move-down')">
            <IFluentArrowDown24Regular class="mr-1 size-4" />
            {{ $t('rich-content.move_down') }}
          </Button>
        </div>
        <div :class="(canMoveUp || canMoveDown) && 'border-t border-border pt-3'">
          <Button type="button" variant="destructive" size="sm" :disabled="!canRemove" @click="$emit('remove')">
            <IFluentDelete24Regular class="mr-1 size-4" />
            {{ $t('rich-content.grid_remove_row') }}
          </Button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
/**
 * Per-row settings popover for content-grid — row-level counterpart to
 * RCGridColumnOptions. Kept out of the block-level More Options toolbar, same reasoning:
 * see RICH_CONTENT_EDITOR.md.
 */
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { injectActiveHotspot } from '../Editor/Fullscreen/useActiveHotspot';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverAnchor, PopoverContent } from '@/Components/ui/popover';
import IFluentArrowDown24Regular from '~icons/fluent/arrow-down24-regular';
import IFluentArrowUp24Regular from '~icons/fluent/arrow-up24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentMoreVertical24Regular from '~icons/fluent/more-vertical24-regular';

const props = defineProps<{
  blockKey: string;
  rowIndex: number;
  canMoveUp: boolean;
  canMoveDown: boolean;
  canRemove: boolean;
}>();

defineEmits<{
  (e: 'move-up'): void;
  (e: 'move-down'): void;
  (e: 'remove'): void;
}>();

const hotspots = injectActiveHotspot();
const hotspotId = computed(() => `${props.blockKey}:row-${props.rowIndex}`);
const triggerRef = ref<HTMLElement | null>(null);

function onOpenChange(open: boolean): void {
  if (open) hotspots.openPopover(hotspotId.value);
  else hotspots.close(hotspotId.value);
}
</script>
