<template>
  <div v-if="images.length > 0">
    <div class="mb-6 flex items-baseline gap-3 border-l-2 border-brand pl-3">
      <h2 class="u-display text-xl font-bold tracking-tight text-foreground lg:text-2xl">
        {{ $t("Nuotraukos") }}
      </h2>
      <span class="font-mono text-sm text-muted-foreground">
        ({{ images.length }})
      </span>
    </div>

    <!-- Main Gallery Grid -->
    <div class="gallery-grid">
      <!-- First image (hero) -->
      <div
        class="gallery-hero group cursor-pointer border border-border"
        tabindex="0"
        role="button"
        :aria-label="`${$t('Atidaryti nuotrauką')} 1 iš ${images.length}`"
        @click="openLightbox(0)"
        @keyup.enter="openLightbox(0)"
      >
        <img
          :src="images[0]?.original_url"
          :alt="`${eventTitle} nuotrauka 1`"
          class="h-full w-full object-cover transition-all duration-300 group-hover:scale-105"
          loading="lazy"
        >
        <div class="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/20" />
        <div
          class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100"
        >
          <IFluentZoomIn24Regular class="h-8 w-8 text-white drop-shadow-lg" />
        </div>
      </div>

      <!-- Secondary images -->
      <div v-if="images.length > 1" class="gallery-secondary">
        <div
          v-for="(image, index) in images.slice(1, images.length > maxVisibleImages ? maxVisibleImages - 1 : maxVisibleImages)"
          :key="image.id"
          class="gallery-item group cursor-pointer border border-border"
          tabindex="0"
          role="button"
          :aria-label="`${$t('Atidaryti nuotrauką')} ${index + 2} iš ${images.length}`"
          @click="openLightbox(index + 1)"
          @keyup.enter="openLightbox(index + 1)"
        >
          <img
            :src="image.original_url"
            :alt="`${eventTitle} nuotrauka ${index + 2}`"
            class="h-full w-full object-cover transition-all duration-300 group-hover:scale-105"
            loading="lazy"
          >
          <div class="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/20" />
          <div
            class="absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100"
          >
            <IFluentZoomIn20Regular class="h-6 w-6 text-white drop-shadow-lg" />
          </div>
        </div>

        <!-- Show more overlay -->
        <div
          v-if="images.length > maxVisibleImages"
          class="gallery-item gallery-more group cursor-pointer border border-border"
          tabindex="0"
          role="button"
          :aria-label="`${$t('Peržiūrėti visas nuotraukas')} (+${remainingImagesCount})`"
          @click="openLightbox(maxVisibleImages - 1)"
          @keyup.enter="openLightbox(maxVisibleImages - 1)"
        >
          <img
            :src="images[maxVisibleImages - 1]?.original_url"
            :alt="`${eventTitle} nuotrauka ${maxVisibleImages}`"
            class="h-full w-full object-cover"
            loading="lazy"
          >
          <div class="absolute inset-0 flex items-center justify-center bg-black/60">
            <div class="text-center text-white">
              <IFluentImageMultiple24Regular class="mx-auto mb-2 h-8 w-8" />
              <div class="text-lg font-semibold">
                +{{ remainingImagesCount }}
              </div>
              <div class="text-sm">
                {{ $t('daugiau') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lightbox Modal -->
    <div
      v-if="lightboxOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
    >
      <!-- Backdrop -->
      <button
        type="button"
        class="fixed inset-0 cursor-default bg-black/90"
        :aria-label="$t('Uždaryti')"
        tabindex="-1"
        @click="closeLightbox"
      />
      <!-- Navigation -->
      <Button
        v-if="images.length > 1"
        variant="ghost"
        size="icon"
        class="absolute left-4 top-1/2 z-10 h-12 w-12 -translate-y-1/2 border border-white/20 bg-black/40 text-white hover:bg-black/60"
        :disabled="currentImageIndex === 0"
        :aria-label="$t('Ankstesnė nuotrauka')"
        @click.stop="previousImage"
      >
        <IFluentChevronLeft24Regular class="h-6 w-6" />
      </Button>

      <Button
        v-if="images.length > 1"
        variant="ghost"
        size="icon"
        class="absolute right-4 top-1/2 z-10 h-12 w-12 -translate-y-1/2 border border-white/20 bg-black/40 text-white hover:bg-black/60"
        :disabled="currentImageIndex === images.length - 1"
        :aria-label="$t('Kita nuotrauka')"
        @click.stop="nextImage"
      >
        <IFluentChevronRight24Regular class="h-6 w-6" />
      </Button>

      <!-- Close button -->
      <Button
        variant="ghost"
        size="icon"
        class="absolute right-4 top-4 z-10 h-10 w-10 border border-white/20 bg-black/40 text-white hover:bg-black/60"
        :aria-label="$t('Uždaryti')"
        @click.stop="closeLightbox"
      >
        <IFluentDismiss20Regular class="h-5 w-5" />
      </Button>

      <!-- Image counter -->
      <div class="absolute left-4 top-4 z-10 border border-white/20 bg-black/70 px-3 py-1 font-mono text-xs text-white">
        {{ currentImageIndex + 1 }} / {{ images.length }}
      </div>

      <!-- Main image -->
      <div class="relative max-h-full max-w-full">
        <img
          :src="currentImage?.original_url"
          :alt="`${eventTitle} nuotrauka ${currentImageIndex + 1}`"
          class="max-h-full max-w-full object-contain"
          @load="handleImageLoad"
        >

        <!-- Loading state -->
        <div v-if="imageLoading" class="absolute inset-0 flex items-center justify-center">
          <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-white" />
        </div>
      </div>

      <!-- Image info -->
      <div v-if="currentImage?.caption" class="absolute bottom-4 left-4 right-4 z-10 text-center">
        <div class="inline-block border border-white/20 bg-black/70 px-4 py-2 text-sm text-white backdrop-blur-sm">
          {{ currentImage.caption }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import Button from '@/Components/ui/button/Button.vue';

interface Props {
  images: Array<{
    id: number;
    original_url: string;
    caption?: string;
    attribution?: string;
  }>;
  eventTitle: string;
  maxVisibleImages?: number;
}

const props = withDefaults(defineProps<Props>(), {
  maxVisibleImages: 5,
});

// Reactive state
const lightboxOpen = ref(false);
const currentImageIndex = ref(0);
const imageLoading = ref(false);

// Computed properties
const currentImage = computed(() => {
  return props.images[currentImageIndex.value];
});

const remainingImagesCount = computed(() => {
  return Math.max(0, props.images.length - props.maxVisibleImages);
});

// Lightbox functionality
const openLightbox = (index: number) => {
  currentImageIndex.value = index;
  lightboxOpen.value = true;
  document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
  lightboxOpen.value = false;
  document.body.style.overflow = '';
};

const nextImage = () => {
  if (currentImageIndex.value < props.images.length - 1) {
    imageLoading.value = true;
    currentImageIndex.value++;
  }
};

const previousImage = () => {
  if (currentImageIndex.value > 0) {
    imageLoading.value = true;
    currentImageIndex.value--;
  }
};

const handleImageLoad = () => {
  imageLoading.value = false;
};

// Keyboard navigation
const handleKeydown = (event: KeyboardEvent) => {
  if (!lightboxOpen.value) return;

  switch (event.key) {
    case 'ArrowRight':
      event.preventDefault();
      nextImage();
      break;
    case 'ArrowLeft':
      event.preventDefault();
      previousImage();
      break;
    case 'Escape':
      event.preventDefault();
      closeLightbox();
      break;
  }
};

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>

<style scoped>
/* Gallery Grid Layout */
.gallery-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
  height: 280px;
}

.gallery-hero {
  position: relative;
  overflow: hidden;
  max-height: 280px;
}

.gallery-secondary {
  display: grid;
  grid-template-rows: repeat(auto-fit, 1fr);
  gap: 0.75rem;
  max-height: 280px;
  overflow: hidden;
}

.gallery-item {
  position: relative;
  overflow: hidden;
  min-height: 80px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .gallery-grid {
    grid-template-columns: 1fr;
    height: auto;
  }

  .gallery-hero {
    height: 200px;
    max-height: 200px;
  }

  .gallery-secondary {
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: none;
    max-height: none;
  }

  .gallery-item {
    aspect-ratio: 1;
    max-height: none;
  }
}

@media (max-width: 480px) {
  .gallery-secondary {
    grid-template-columns: 1fr;
  }
}

/* Ensure images don't overflow their containers */
.gallery-hero img,
.gallery-item img {
  object-fit: cover;
  object-position: center;
  width: 100%;
  height: 100%;
  display: block;
}

/* Interactive states */
.gallery-item:focus,
.gallery-hero:focus {
  outline: 2px solid var(--brand);
  outline-offset: 2px;
}

.gallery-item:focus-visible,
.gallery-hero:focus-visible {
  outline: 2px solid var(--brand);
  outline-offset: 2px;
}

/* Animations */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

/* Backdrop blur support */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Loading state */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}

/* Drop shadow for text readability */
.drop-shadow-lg {
  filter: drop-shadow(0 10px 8px rgb(0 0 0 / 0.04)) drop-shadow(0 4px 3px rgb(0 0 0 / 0.1));
}
</style>
