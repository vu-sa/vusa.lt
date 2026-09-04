<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :band :align="element.options?.align ?? 'center'"
    :heading-level="element.options?.headingLevel" :show-separator="element.options?.showSeparator"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
      <div :class="['grid grid-cols-2 relative z-10', gridClass, gapClass]">
        <div
          v-for="(column, columnIndex) in columns"
          :key="columnIndex"
          :class="['space-y-2 md:space-y-4', columnIndex % 2 === 1 ? 'mt-4 md:mt-8' : '']"
        >
          <div
            v-for="entry in column"
            :key="entry.index"
            class="relative group cursor-pointer"
            @click="openLightbox(entry.index)"
          >
            <ImageWithDecorations
              :src="entry.image.src"
              :alt="entry.image.alt"
              :height-class="entry.image.heightClass || 'h-52'"
              :decorations="entry.image.decorations"
              :object-position="entry.image.objectPosition"
              :hover-scale="true"
              loading="lazy"
            />
            <!-- Lightbox overlay hint -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100">
              <div class="bg-background/90 p-2 transform scale-75 group-hover:scale-100 transition-transform duration-300">
                <svg class="w-5 h-5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lightbox: teleported to <body> — the canvas is inside a `contain: layout` ancestor,
           which becomes the containing block for position:fixed, so without this the modal
           would be clipped to the content column instead of covering the viewport. -->
      <VueEasyLightbox
        v-if="element.options?.showLightbox"
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
import { computed, ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import RCSection from '../RCSection.vue';
import type { PhotoGalleryGrid } from '@/Types/contentParts';
import type { BandResolution } from '../bandLayout';

const { element } = defineProps<{
  element: PhotoGalleryGrid;
  anchorId?: number | null;
  band?: BandResolution;
}>();

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
  '2': '',
  '3': 'md:grid-cols-3',
  '4': 'md:grid-cols-4',
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
  lightboxIndex.value = index;
  lightboxVisible.value = true;
};

const closeLightbox = () => {
  lightboxVisible.value = false;
};
</script>