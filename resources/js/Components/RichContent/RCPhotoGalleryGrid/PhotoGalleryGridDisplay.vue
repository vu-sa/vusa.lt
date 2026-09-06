<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="element.options?.title" :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow" :band
    :align="element.options?.align ?? 'center'" :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator" inner="wide"
    :editable @update:header="updateOptions"
  >
    <div class="relative">
      <RCAddPlaceholder v-if="editable && !element.json_content.length" :label="$t('rich-content.add_image')" @click="addEmptyImage" />
      <div v-else :class="['grid grid-cols-2 relative z-10', gridClass, gapClass]">
        <div
          v-for="(column, columnIndex) in columns"
          :key="columnIndex"
          :class="['space-y-2 md:space-y-4', columnIndex % 2 === 1 ? 'mt-4 md:mt-8' : '']"
        >
          <div
            v-for="entry in column"
            :key="entry.index"
            class="relative group cursor-pointer"
            role="button"
            tabindex="0"
            @click="openLightbox(entry.index)"
            @keydown.enter="openLightbox(entry.index)"
            @keydown.space.prevent="openLightbox(entry.index)"
          >
            <ImageWithDecorations
              :src="entry.image.src"
              :alt="entry.image.alt"
              :height-class="entry.image.heightClass || 'h-52'"
              :decorations="entry.image.decorations"
              :object-position="entry.image.objectPosition"
              hover-scale
              loading="lazy"
            />
            <RCImageHotspot
              v-if="editable"
              :image-url="entry.image.src"
              :alt="entry.image.alt"
              :object-position="entry.image.objectPosition"
              :block-key="blockKey ?? ''"
              :image-index="entry.index"
              full-tile-trigger
              :can-move-up="entry.index > 0"
              :can-move-down="entry.index < element.json_content.length - 1"
              :can-delete="element.json_content.length > 1"
              @update:image="replaceImage(entry.index, $event)"
              @update:alt="updateImage(entry.index, { alt: $event })"
              @update:object-position="updateImage(entry.index, { objectPosition: $event })"
              @delete="removeImage(entry.index)"
              @move-up="moveImage(entry.index, entry.index - 1)"
              @move-down="moveImage(entry.index, entry.index + 1)"
            >
              <template #options>
                <Field class="border-t border-border pt-4">
                  <FieldLabel>{{ $t('rich-content.height_class') }}</FieldLabel>
                  <Select :model-value="entry.image.heightClass || 'h-52'" @update:model-value="updateImage(entry.index, { heightClass: $event as string })">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="h-32">
                        {{ $t('rich-content.small') }}
                      </SelectItem><SelectItem value="h-40">
                        {{ $t('rich-content.medium_small') }}
                      </SelectItem><SelectItem value="h-52">
                        {{ $t('rich-content.medium') }}
                      </SelectItem><SelectItem value="h-64">
                        {{ $t('rich-content.large') }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
                <RCDecorationListEditor :model-value="entry.image.decorations ?? []" @update:model-value="updateImage(entry.index, { decorations: $event })" />
              </template>
            </RCImageHotspot>
            <!-- Lightbox overlay hint -->
            <div
              v-if="!editable"
              class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/0 opacity-0 transition-all duration-300 group-hover:bg-black/20 group-hover:opacity-100"
            >
              <div class="bg-background/90 p-2 transform scale-75 group-hover:scale-100 transition-transform duration-300">
                <svg class="w-5 h-5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
      <RCAddPlaceholder
        v-if="editable && element.json_content.length"
        :label="$t('rich-content.add_image')"
        class="bottom-0 right-0 translate-x-full translate-y-1/2"
        @click="addEmptyImage"
      />
    </div>

    <!-- Lightbox: teleported to <body> — the canvas is inside a `contain: layout` ancestor,
           which becomes the containing block for position:fixed, so without this the modal
           would be clipped to the content column instead of covering the viewport. -->
    <VueEasyLightbox
      v-if="element.options?.showLightbox && !editable"
      teleport="body"
      class="z-50"
      :visible="lightboxVisible"
      :imgs="lightboxImages"
      :index="lightboxIndex"
      scroll-disabled
      loop
      @hide="closeLightbox"
    />
  </RCSection>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, inject, nextTick, ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

import RCSection from '../RCSection.vue';
import type { BandResolution } from '../bandLayout';
import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';

import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import type { PhotoGalleryGrid } from '@/Types/contentParts';

// Lazy-loaded: only ever mounted while `editable` — a static import would bundle the
// hotspot's image picker/focal-point UI, the decoration-list editor, and the
// height-select field into every public page that renders a photo gallery.
const RCAddPlaceholder = defineAsyncComponent(() => import('../Editor/Fullscreen/RCAddPlaceholder.vue'));
const RCImageHotspot = defineAsyncComponent(() => import('../Editor/Fullscreen/RCImageHotspot.vue'));
const RCDecorationListEditor = defineAsyncComponent(() => import('../Editor/RCDecorationListEditor.vue'));
const Field = defineAsyncComponent(() => import('@/Components/ui/field').then(m => m.Field));
const FieldLabel = defineAsyncComponent(() => import('@/Components/ui/field').then(m => m.FieldLabel));
const Select = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.Select));
const SelectContent = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectContent));
const SelectItem = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectItem));
const SelectTrigger = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectTrigger));
const SelectValue = defineAsyncComponent(() => import('@/Components/ui/select').then(m => m.SelectValue));

const { element, editable } = defineProps<{
  element: PhotoGalleryGrid;
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: the optional title/subtitle/eyebrow header becomes
   *  click-to-edit. Undefined/false in every other context. */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this block has no per-field claiming, but an
   *  undeclared non-undefined prop would otherwise land on the root as a stray attribute. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: PhotoGalleryGrid) => void>();
const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...element, options: { ...element.options, ...patch } });
}

function updateImage(index: number, patch: Partial<PhotoGalleryGrid['json_content'][number]>): void {
  const json_content = [...element.json_content];
  const image = json_content[index];
  if (!image) return;
  json_content[index] = { ...image, ...patch };
  emit('update:element', { ...element, json_content });
}

function replaceImage(index: number, image: { src: string; alt: string; title: string }): void {
  updateImage(index, { src: image.src, alt: image.alt, title: image.title });
}

function removeImage(index: number): void {
  if (element.json_content.length <= 1) return;
  emit('update:element', { ...element, json_content: element.json_content.filter((_, currentIndex) => currentIndex !== index) });
}

function moveImage(from: number, to: number): void {
  const json_content = [...element.json_content];
  const [image] = json_content.splice(from, 1);
  if (!image) return;
  json_content.splice(to, 0, image);
  emit('update:element', { ...element, json_content });
}

async function addEmptyImage(): Promise<void> {
  emit('update:element', { ...element, json_content: [...element.json_content, { src: '', alt: '', heightClass: 'h-52', decorations: [] }] });
  await nextTick();
  hotspots?.openPopover(`${blockKey ?? ''}:image-${element.json_content.length}`);
}

// Lightbox state
const lightboxVisible = ref(false);
const lightboxIndex = ref(0);

// Prepare images for lightbox (convert to format expected by vue-easy-lightbox)
const lightboxImages = computed(() =>
  element.json_content.map(image => ({
    src: image.src,
    title: image.alt,
  })),
);

// Grid/gap classes based on options — static maps so Tailwind's JIT can see every candidate
// (a template-literal class like `md:grid-cols-${n}` is invisible to the scanner).
const GRID_COLS_CLASS: Record<string, string> = {
  2: '',
  3: 'md:grid-cols-3',
  4: 'md:grid-cols-4',
};
const GAP_CLASS: Record<string, string> = {
  small: 'gap-1 md:gap-2',
  medium: 'gap-2 md:gap-4',
  large: 'gap-4 md:gap-6',
};

const gridClass = computed(() => GRID_COLS_CLASS[element.options?.columns || '4'] ?? GRID_COLS_CLASS['4']);
const gapClass = computed(() => GAP_CLASS[element.options?.gap || 'medium'] ?? GAP_CLASS['medium']);

type GalleryImage = PhotoGalleryGrid['json_content'][number];

// Distribute images across columns (round-robin), carrying each image's original index
// so the lightbox always opens the photo that was actually clicked.
const columns = computed(() => {
  const columnCount = parseInt(element.options?.columns || '4');
  const cols: { image: GalleryImage; index: number }[][] = Array.from({ length: columnCount }, () => []);

  element.json_content.forEach((image, index) => {
    const columnIndex = index % columnCount;
    cols[columnIndex]?.push({ image, index });
  });

  return cols;
});

// Lightbox functions
const openLightbox = (index: number) => {
  if (element.options?.showLightbox === false || editable) return;
  lightboxIndex.value = index;
  lightboxVisible.value = true;
};

const closeLightbox = () => {
  lightboxVisible.value = false;
};
</script>
