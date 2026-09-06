<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="flex h-[min(90vh,50rem)] w-[min(96vw,90rem)] max-w-none flex-col gap-0 overflow-hidden p-0 sm:max-w-none">
      <DialogHeader class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
        <DialogTitle class="flex items-center gap-2 text-base">
          <component :is="contentType.icon" class="h-4 w-4 text-zinc-500" />
          {{ contentType.label }}
        </DialogTitle>
        <DialogDescription class="sr-only">
          {{ $t('rich-content.side_by_side_description') }}
        </DialogDescription>
      </DialogHeader>

      <!-- Gated on `open`: this mounts a second, independent editor instance (e.g. a
           second TipTap) bound to the same content — it must not exist while the
           dialog is closed, or it would run alongside the inline block's own editor. -->
      <div v-if="open" class="grid min-h-0 flex-1 grid-cols-2 divide-x divide-zinc-100 dark:divide-zinc-800">
        <!-- Editor pane — the exact same editor component the inline block uses, so
             nothing behaves differently here. -->
        <div class="overflow-y-auto p-4">
          <ContentEditorFactory :content="content" :tenant-id="tenantId" @update:content="$emit('update:content', $event)" />
        </div>

        <!-- Preview pane — reactive to every edit on the left. The width picker is
             the same one the inline block card uses (same per-type `allowedWidths`
             from the registry), and writes back to the block's saved `options.width`
             through the usual `update:content` channel — what you see is what's saved.
             The dark-mode toggle drives the admin theme itself via useDark() (see
             isDark) — a local `.dark` on this subtree can't work, because Tailwind's
             `dark:` variant (`&:is(.dark *)`) matches any `.dark` ancestor: it can
             force the preview dark but can never force it light when <html> is already
             dark. -->
        <div class="flex flex-col overflow-hidden">
          <div class="flex h-full flex-col overflow-hidden bg-zinc-50 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-200 px-3 py-2 dark:border-zinc-800">
              <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $t('rich-content.block_width') }}</span>
              <div class="flex items-center gap-1">
                <RCWidthPicker
                  v-if="allowedWidths.length > 1"
                  :model-value="currentWidth"
                  :allowed-widths="allowedWidths"
                  @update:model-value="setWidth"
                />
                <button
                  type="button"
                  class="flex h-6 w-6 items-center justify-center rounded text-zinc-500 transition-colors hover:bg-zinc-200 dark:text-zinc-400 dark:hover:bg-zinc-700"
                  :title="$t('rich-content.preview_dark_mode')"
                  @click="isDark = !isDark"
                >
                  <IFluentWeatherMoon24Regular v-if="isDark" class="h-3.5 w-3.5" />
                  <IFluentWeatherSunny24Regular v-else class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
            <div class="relative flex-1 overflow-auto p-4">
              <div class="mx-auto overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div
                  class="rc-canvas origin-top-left bg-zinc-50 dark:bg-zinc-900"
                  :style="{ width: `${PREVIEW_WIDTH}px`, transform: `scale(${PREVIEW_SCALE})`, '--rc-measure': '40rem' }"
                >
                  <BlockPreviewRenderer :element="content" :resolved="previewResolved" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { useDark } from '@vueuse/core';
import { computed, toRef } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import ContentEditorFactory from '../ContentEditorFactory.vue';
import { useLiveBlockPreview } from '../composables/useLiveBlockPreview';
import { getContentType, type BlockWidth, type ContentPart } from '../Types';
import BlockPreviewRenderer from './BlockPreviewRenderer.vue';
import { withWidth } from './blockWidth';
import RCWidthPicker from './RCWidthPicker.vue';

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import IFluentWeatherMoon24Regular from '~icons/fluent/weather-moon24-regular';
import IFluentWeatherSunny24Regular from '~icons/fluent/weather-sunny24-regular';

// Wide enough that section-chrome blocks (hero, card-stack, …) render at something
// close to their real proportions before being scaled down to fit the pane — same
// values BlockPickerDialog uses, for the same reason.
const PREVIEW_WIDTH = 1280;
const PREVIEW_SCALE = 0.5;

const props = defineProps<{
  open: boolean;
  content: ContentPart;
  /** Tenant the page/news article being edited belongs to — for server-resolved previews. */
  tenantId?: number | null;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'update:content', value: ContentPart): void;
}>();

const contentType = computed(() => getContentType(props.content?.type));

// Mirrors RCBlockCard: same per-type subset from the registry, same write-back path.
// Section blocks (whose only allowed width is 'full') hide the picker via the
// `allowedWidths.length > 1` v-if in the template, matching the inline card.
const allowedWidths = computed<BlockWidth[]>(() => contentType.value.allowedWidths ?? [contentType.value.defaultWidth]);
const currentWidth = computed<BlockWidth>(() => (props.content?.options?.width as BlockWidth | undefined) ?? contentType.value.defaultWidth);

function setWidth(width: BlockWidth) {
  emit('update:content', withWidth(props.content, width));
}

// The preview's dark-mode toggle *is* the admin theme: useDark() writes the `.dark`
// class to <html>, flipping the whole panel (preview included) in both directions.
// A local `.dark` on just the preview subtree can't work — Tailwind's `dark:` variant
// (`&:is(.dark *)`) matches any `.dark` ancestor, so it could force the preview dark but
// never force it light when the admin theme is already dark.
const isDark = useDark();

// Gated on `open` too — this composable's watch lives for as long as the dialog
// component instance does, which (per the `v-if="open"` above) is only while open,
// but the extra guard also protects a hypothetical future call site that mounts
// this dialog unconditionally with `v-model:open`.
const { previewResolved } = useLiveBlockPreview(toRef(props, 'content'), () => props.tenantId, () => props.open);
</script>
