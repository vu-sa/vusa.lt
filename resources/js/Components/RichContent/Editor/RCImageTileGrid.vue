<template>
  <div class="space-y-3">
    <!-- Empty state -->
    <div v-if="!modelValue?.length"
      class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-200 p-6 text-center dark:border-zinc-700">
      <IFluentImageMultiple24Regular class="mb-2 h-8 w-8 text-zinc-400 dark:text-zinc-500" />
      <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ emptyText ?? $t('rich-content.no_images') }}
      </p>
      <TiptapImageButton class="mt-3" @submit:object="addImage">
        {{ addFirstText ?? $t('rich-content.add_first_image') }}
      </TiptapImageButton>
    </div>

    <!-- Tile grid — same column proportions the display renders, so the editor mirrors
         the output instead of a stacked list of unrelated rows. -->
    <div v-else ref="gridEl" class="grid gap-3" :style="{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }">
      <div v-for="(item, index) in modelValue" :key="index"
        class="group relative overflow-hidden rounded-xl ring-1 ring-zinc-200/60 transition-shadow hover:ring-vusa-red/40 dark:ring-zinc-700/50"
        :class="[tileClass ? resolveTileClass(item, index) : 'aspect-4/3', spanClass ? resolveSpanClass(item, index) : '']">
        <!-- Drag handle -->
        <div
          class="rc-image-drag-handle absolute left-1.5 top-1.5 z-10 flex h-6 w-6 cursor-grab items-center justify-center rounded bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 active:cursor-grabbing"
          :title="$t('rich-content.drag_to_reorder')"
        >
          <IFluentReOrderDotsVertical24Regular class="h-3.5 w-3.5" />
        </div>

        <!-- Tile menu: focal point, per-type extras (slot), remove -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button type="button"
              class="absolute right-1.5 top-1.5 z-10 flex h-6 w-6 items-center justify-center rounded bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100"
              :title="$t('rich-content.tile_options')">
              <IFluentMoreHorizontal24Regular class="h-3.5 w-3.5" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem :disabled="!getSrc(item)" @click="openFocalPoint(index)">
              <IFluentTarget24Regular class="mr-2 h-4 w-4" />
              {{ $t('rich-content.set_focal_point') }}
            </DropdownMenuItem>
            <slot name="tile-menu" :item="item" :index="index" :update="(patch: Partial<T>) => updateAt(index, patch)" />
            <DropdownMenuSeparator />
            <DropdownMenuItem class="text-red-600 focus:text-red-600" @click="removeAt(index)">
              <IFluentDelete24Regular class="mr-2 h-4 w-4" />
              {{ $t('common.delete') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Image / click to replace -->
        <TiptapImageButton as-child @submit:object="(img) => replaceAt(index, img)">
          <button type="button" class="block h-full w-full">
            <img v-if="getSrc(item)" :src="getSrc(item)" :alt="(item as any).alt || ''"
              class="h-full w-full object-cover" :style="{ objectPosition: (item as any).objectPosition }">
            <div v-else class="flex h-full w-full flex-col items-center justify-center gap-1 text-zinc-400 dark:text-zinc-500">
              <IFluentImageAdd24Regular class="h-6 w-6" />
              <span class="text-xs">{{ $t('rich-content.select_image') }}</span>
            </div>
          </button>
        </TiptapImageButton>

        <!-- Inline alt text + any per-type footer control (e.g. width picker) -->
        <div class="flex items-center gap-1.5 border-t border-zinc-200 bg-white p-1.5 dark:border-zinc-700 dark:bg-zinc-900">
          <Input
            :model-value="(item as any).alt"
            type="text"
            class="h-7 flex-1 text-xs"
            :placeholder="$t('rich-content.image_alt_placeholder')"
            @update:model-value="updateAt(index, { alt: $event as string } as Partial<T>)"
          />
          <slot name="tile-footer" :item="item" :index="index" :update="(patch: Partial<T>) => updateAt(index, patch)" />
        </div>
      </div>
    </div>

    <TiptapImageButton as-child @submit:object="addImage">
      <button type="button"
        class="flex w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800/50">
        <IFluentAdd24Regular class="h-3.5 w-3.5" />
        {{ addText ?? $t('rich-content.add_image') }}
      </button>
    </TiptapImageButton>

    <!-- Focal point dialog -->
    <Dialog v-model:open="showFocalPoint">
      <DialogContent class="max-w-xl">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.set_focal_point') }}</DialogTitle>
        </DialogHeader>
        <FocalPointPicker
          v-if="focalPointIndex !== null && getSrc(modelValue![focalPointIndex])"
          :image-url="getSrc(modelValue![focalPointIndex])!"
          :model-value="(modelValue![focalPointIndex] as any).objectPosition ?? null"
          @update:model-value="(val: string) => updateAt(focalPointIndex!, { objectPosition: val } as Partial<T>)"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts" generic="T extends Record<string, any>">
/**
 * Shared thumbnail-grid image editor behind ImageGridEditor and PhotoGalleryGridEditor.
 * Tiles are laid out in the same proportions the display renders, click-to-replace,
 * drag-to-reorder, with inline alt text and a hover menu for focal point + per-type
 * options (colspan for image-grid, height/decorations for photo-gallery) supplied
 * through the `tile-menu` / `tile-footer` slots — so the two editors share one
 * implementation instead of drifting (photo-gallery used to be a completely separate
 * stacked-rows DynamicListInput UI with alt collected twice).
 */
import { ref, watch } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';

import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';
import IFluentAdd24Regular from '~icons/fluent/add24-regular';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';
import IFluentImageAdd24Regular from '~icons/fluent/image-add24-regular';
import IFluentImageMultiple24Regular from '~icons/fluent/image-multiple24-regular';
import IFluentMoreHorizontal24Regular from '~icons/fluent/more-horizontal24-regular';
import IFluentReOrderDotsVertical24Regular from '~icons/fluent/re-order-dots-vertical24-regular';
import IFluentTarget24Regular from '~icons/fluent/target24-regular';

const props = withDefaults(defineProps<{
  /** Which key on each item holds the image URL — `image` (ImageGrid) or `src` (PhotoGalleryGrid). */
  srcKey?: string;
  /** Reference column count for the editor grid (doesn't have to match the display's masonry). */
  columns?: number;
  /** Per-tile extra class (e.g. colspan) driven by the item itself. */
  tileClass?: (item: T, index: number) => string;
  /** Per-tile grid-column span class, applied alongside tileClass. */
  spanClass?: (item: T, index: number) => string;
  emptyText?: string;
  addFirstText?: string;
  addText?: string;
  createItem: () => T;
}>(), {
  srcKey: 'src',
  columns: 3,
  tileClass: undefined,
  spanClass: undefined,
});

const modelValue = defineModel<T[]>({ default: () => [] });

function getSrc(item: T): string | undefined {
  return item?.[props.srcKey];
}

function resolveTileClass(item: T, index: number): string {
  return props.tileClass ? props.tileClass(item, index) : '';
}

function resolveSpanClass(item: T, index: number): string {
  return props.spanClass ? props.spanClass(item, index) : '';
}

function updateAt(index: number, patch: Partial<T>) {
  const next = [...(modelValue.value ?? [])];
  const current = next[index];
  if (!current) return;
  next[index] = { ...current, ...patch };
  modelValue.value = next;
}

function removeAt(index: number) {
  const next = [...(modelValue.value ?? [])];
  next.splice(index, 1);
  modelValue.value = next;
}

function replaceAt(index: number, imageData: { src: string; alt: string; title: string }) {
  updateAt(index, { [props.srcKey]: imageData.src, alt: imageData.alt, title: imageData.title } as Partial<T>);
}

function addImage(imageData: { src: string; alt: string; title: string }) {
  const base = props.createItem();
  modelValue.value = [
    ...(modelValue.value ?? []),
    { ...base, [props.srcKey]: imageData.src, alt: imageData.alt, title: imageData.title },
  ];
}

// Focal point dialog
const showFocalPoint = ref(false);
const focalPointIndex = ref<number | null>(null);
function openFocalPoint(index: number) {
  focalPointIndex.value = index;
  showFocalPoint.value = true;
}

// Drag-to-reorder, same mechanism as RichContentEditor's block list.
const gridEl = ref<HTMLElement | null>(null);
let stopSortable: (() => void) | null = null;

watch(gridEl, (newEl) => {
  if (stopSortable) {
    stopSortable();
    stopSortable = null;
  }
  if (newEl) {
    const { stop } = useSortable(newEl, modelValue, {
      handle: '.rc-image-drag-handle',
      animation: 150,
      ghostClass: 'opacity-50',
    });
    stopSortable = stop;
  }
}, { immediate: true });
</script>
