<template>
  <section class="py-2 px-4 max-w-7xl mx-auto relative">
    <!-- Section decorative elements -->
    <!-- <DecorativeElement  -->
    <!--   type="line"  -->
    <!--   position="top-right"  -->
    <!--   size="md"  -->
    <!--   color="vusa-yellow"  -->
    <!--   :opacity="40"  -->
    <!-- /> -->
    <!-- <DecorativeElement  -->
    <!--   type="square"  -->
    <!--   position="bottom-left"  -->
    <!--   size="sm"  -->
    <!--   color="vusa-red"  -->
    <!--   :opacity="30"  -->
    <!--   :rotation="true"  -->
    <!-- /> -->
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-4 relative z-10">
      <div 
        v-for="(column, columnIndex) in columns" 
        :key="columnIndex"
        :class="['space-y-2 md:space-y-4', columnIndex % 2 === 1 ? 'mt-4 md:mt-8' : '']"
      >
        <div 
          v-for="(image, imageIndex) in column" 
          :key="imageIndex"
          class="relative group cursor-pointer"
          @click="openLightbox(getImageIndex(columnIndex, imageIndex))"
        >
          <ImageWithDecorations
            :src="image.src"
            :alt="image.alt"
            :height-class="image.heightClass"
            :decorations="image.decorations"
            :hover-scale="true"
            loading="lazy"
          />
          <!-- Lightbox overlay hint -->
          <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all duration-300 rounded-xl flex items-center justify-center opacity-0 group-hover:opacity-100">
            <div class="bg-white/90 dark:bg-zinc-800/90 rounded-full p-2 transform scale-75 group-hover:scale-100 transition-transform duration-300">
              <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox -->
    <VueEasyLightbox
      :visible="lightboxVisible"
      :imgs="lightboxImages"
      :index="lightboxIndex"
      @hide="closeLightbox"
      :loop="true"
      :scroll-disabled="true"
      :move-disabled="false"
      :esc-disabled="false"
      :dbl-click-disabled="false"
    />
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import DecorativeElement from '@/Components/ui/DecorativeElement.vue';

interface GalleryImage {
  src: string;
  alt: string;
  heightClass: string;
  decorations?: Array<{
    type: 'circle' | 'line' | 'square';
    position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
    size: 'sm' | 'md' | 'lg';
    color?: 'vusa-red' | 'vusa-yellow' | 'zinc';
    opacity?: number;
    rotation?: boolean;
  }>;
}

interface Props {
  images: GalleryImage[];
}

const props = defineProps<Props>();

// Lightbox state
const lightboxVisible = ref(false);
const lightboxIndex = ref(0);

// Prepare images for lightbox (convert to format expected by vue-easy-lightbox)
const lightboxImages = computed(() => 
  props.images.map(image => ({
    src: image.src,
    title: image.alt,
  }))
);

// Distribute images across 4 columns with masonry layout
const columns = computed(() => {
  const columnCount = 4;
  const cols: GalleryImage[][] = Array.from({ length: columnCount }, () => []);
  
  props.images.forEach((image, index) => {
    const columnIndex = index % columnCount;
    const column = cols[columnIndex];
    if (column) {
      column.push(image);
    }
  });
  
  return cols;
});

// Helper function to get the global index of an image from its column/row position
const getImageIndex = (columnIndex: number, imageIndex: number): number => {
  let globalIndex = 0;
  
  // Count images in previous columns
  for (let col = 0; col < columnIndex; col++) {
    globalIndex += columns.value[col]?.length || 0;
  }
  
  // Add the index within the current column
  globalIndex += imageIndex;
  
  return globalIndex;
};

// Lightbox functions
const openLightbox = (index: number) => {
  lightboxIndex.value = index;
  lightboxVisible.value = true;
};

const closeLightbox = () => {
  lightboxVisible.value = false;
};
</script>

<style>
/* Custom lightbox styles to match the design system */
.vel-modal {
  backdrop-filter: blur(8px);
}

.vel-modal .vel-img {
  border-radius: 12px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.vel-modal .vel-toolbar {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(12px);
  border-radius: 12px;
}

@media (prefers-color-scheme: dark) {
  .vel-modal .vel-toolbar {
    background: rgba(39, 39, 42, 0.9);
    color: #f4f4f5;
  }
  
  .vel-modal .vel-toolbar .vel-btn {
    color: #f4f4f5;
  }
}
</style>
