<template>
  <div class="space-y-3">
    <div v-if="showFocalPoint && isMobile" class="space-y-4">
      <Button variant="ghost" size="sm" @click="showFocalPoint = false">
        <ArrowLeft class="size-4" />
        {{ $t('rich-content.back_to_images') }}
      </Button>
      <FocalPointPicker
        v-if="focalPointImage && getSrc(focalPointImage)"
        :image-url="getSrc(focalPointImage)!"
        :model-value="focalPointImage.objectPosition ?? null"
        @update:model-value="(val: string) => updateAt(focalPointIndex!, { objectPosition: val } as Partial<T>)"
      />
    </div>
    <template v-else>
    <!-- Empty state -->
    <div v-if="!modelValue?.length"
      class="flex flex-col items-center justify-center border border-dashed border-border p-6 text-center">
      <Images class="mb-2 size-8 text-muted-foreground" />
      <p class="text-sm text-muted-foreground">
        {{ emptyText ?? $t('rich-content.no_images') }}
      </p>
      <TiptapImageButton class="mt-3" @submit:object="addImage">
        {{ addFirstText ?? $t('rich-content.add_first_image') }}
      </TiptapImageButton>
    </div>

    <!-- Tile grid — same column proportions the display renders, so the editor mirrors
         the output instead of a stacked list of unrelated rows. -->
    <div v-else ref="gridEl" class="grid grid-cols-2 gap-3 md:grid-cols-[repeat(var(--tile-columns),minmax(0,1fr))]" :style="{ '--tile-columns': columns }">
      <div v-for="(item, index) in modelValue" :key="index"
        class="group relative overflow-hidden border border-border focus-within:border-brand"
        :class="[tileClass ? resolveTileClass(item, index) : 'aspect-4/3', spanClass ? resolveSpanClass(item, index) : '']">
        <!-- Drag handle -->
        <div
          class="rc-image-drag-handle absolute left-1.5 top-1.5 z-10 flex size-8 cursor-grab items-center justify-center bg-ink/75 text-white pointer-coarse:size-11 active:cursor-grabbing"
          :title="$t('rich-content.drag_to_reorder')"
          aria-hidden="true"
        >
          <GripVertical class="size-4" />
        </div>

        <!-- Tile menu: focal point, per-type extras (slot), remove -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <button type="button"
              class="absolute right-1.5 top-1.5 z-10 flex size-8 items-center justify-center bg-ink/75 text-white focus-visible:ring-2 focus-visible:ring-brand pointer-coarse:size-11"
              :aria-label="$t('rich-content.tile_options')">
              <Ellipsis class="size-4" />
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem :disabled="!getSrc(item)" @click="openFocalPoint(index)">
              <ScanEye class="mr-2 size-4" />
              {{ $t('rich-content.set_focal_point') }}
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="index === 0" @click="moveItem(index, index - 1)">
              <ArrowUp class="mr-2 size-4" />
              {{ $t('rich-content.move_up') }}
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="index === modelValue.length - 1" @click="moveItem(index, index + 1)">
              <ArrowDown class="mr-2 size-4" />
              {{ $t('rich-content.move_down') }}
            </DropdownMenuItem>
            <slot name="tile-menu" :item :index :update="(patch: Partial<T>) => updateAt(index, patch)" />
            <DropdownMenuSeparator />
            <DropdownMenuItem
              class="text-destructive focus:text-destructive"
              :disabled="(modelValue?.length ?? 0) <= 1"
              @click="removeAt(index)"
            >
              <Trash2 class="mr-2 size-4" />
              {{ $t('common.delete') }}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>

        <!-- Image / click to replace -->
        <TiptapImageButton as-child @submit:object="(img) => replaceAt(index, img)">
          <button type="button" class="block h-full w-full" :aria-label="$t('rich-content.replace_image')">
            <img v-if="getSrc(item)" :src="getSrc(item)" :alt="(item as any).alt || ''"
              class="h-full w-full object-cover" :style="{ objectPosition: (item as any).objectPosition }">
            <div v-else class="flex h-full w-full flex-col items-center justify-center gap-1 text-muted-foreground">
              <ImagePlus class="size-6" />
              <span class="text-xs">{{ $t('rich-content.select_image') }}</span>
            </div>
          </button>
        </TiptapImageButton>

        <!-- Inline alt text + any per-type footer control (e.g. width picker) -->
        <div class="flex items-center gap-1.5 border-t border-border bg-background p-1.5">
          <Input
            :model-value="(item as any).alt"
            type="text"
            class="min-w-0 flex-1 text-xs"
            :aria-label="$t('rich-content.image_alt_text')"
            :placeholder="$t('rich-content.image_alt_placeholder')"
            @update:model-value="updateAt(index, { alt: $event as string } as Partial<T>)"
          />
          <slot name="tile-footer" :item :index :update="(patch: Partial<T>) => updateAt(index, patch)" />
        </div>
      </div>
    </div>

    <TiptapImageButton as-child @submit:object="addImage">
      <button type="button"
        class="flex min-h-11 w-full items-center justify-center gap-1.5 border border-dashed border-border px-3 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent">
        <Plus class="size-4" />
        {{ addText ?? $t('rich-content.add_image') }}
      </button>
    </TiptapImageButton>

    <!-- Focal point dialog -->
    </template>
    <Dialog v-if="!isMobile" v-model:open="showFocalPoint">
      <DialogContent class="max-w-xl">
        <DialogHeader>
          <DialogTitle>{{ $t('rich-content.set_focal_point') }}</DialogTitle>
        </DialogHeader>
        <FocalPointPicker
          v-if="focalPointImage && getSrc(focalPointImage)"
          :image-url="getSrc(focalPointImage)!"
          :model-value="focalPointImage.objectPosition ?? null"
          @update:model-value="(val: string) => updateAt(focalPointIndex!, { objectPosition: val } as Partial<T>)"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts" generic="T extends Record<string, any>">
/** Shared tile controls keep image grids and galleries in sync. */
import { computed, ref, watch } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowDown, ArrowLeft, ArrowUp, Ellipsis, GripVertical, ImagePlus, Images, Plus, ScanEye, Trash2 } from 'lucide-vue-next';

import TiptapImageButton from '@/Components/TipTap/TiptapImageButton.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import FocalPointPicker from '@/Components/ui/upload/FocalPointPicker.vue';
import { useIsMobile } from '@/Composables/useIsMobile';

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
const isMobile = useIsMobile();

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
  if ((modelValue.value?.length ?? 0) <= 1) return;
  const next = [...(modelValue.value ?? [])];
  next.splice(index, 1);
  modelValue.value = next;
}

function moveItem(from: number, to: number) {
  const next = [...(modelValue.value ?? [])];
  const [item] = next.splice(from, 1);
  if (!item) return;
  next.splice(to, 0, item);
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
const focalPointImage = computed(() => focalPointIndex.value === null ? null : modelValue.value?.[focalPointIndex.value] ?? null);
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
