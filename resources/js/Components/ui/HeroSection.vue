<template>
  <section class="relative bg-zinc-50 dark:bg-zinc-900 overflow-hidden -mt-8 sm:-mt-6 md:-mt-4 2xl:mt-0 py-20">
    <div class="max-w-6xl mx-auto px-4 relative z-10">
      <div class="grid 2xl:grid-cols-2 gap-6 sm:gap-8 md:gap-10 lg:gap-12 xl:gap-14 2xl:gap-16 items-center">
        <!-- Text Content -->
        <div :class="['space-y-4 sm:space-y-5 md:space-y-6 2xl:space-y-8 2xl:pr-8', textLeft ? 'order-first' : 'order-last 2xl:order-first']">
          <div class="space-y-3 sm:space-y-4 md:space-y-5 2xl:space-y-6">
            <h1
              class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight"
              v-html="title" 
            />
            <p class="text-sm sm:text-base md:text-lg lg:text-xl text-zinc-600 dark:text-zinc-400 leading-relaxed max-w-lg">
              {{ description }}
            </p>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 2xl:gap-4">
            <slot name="buttons" />
          </div>
        </div>

        <!-- Image with overlapping text -->
        <div :class="['relative', textLeft ? 'order-last' : 'order-first 2xl:order-last']">
          <ImageWithDecorations
            :src="imageSrc"
            :alt="imageAlt"
            height-class="h-[240px] sm:h-[280px] md:h-[320px] lg:h-[360px] xl:h-[400px] 2xl:h-[500px]"
            :decorations="imageDecorations"
            :overlay-content="overlayContent"
            :object-position="objectPosition"
            loading="eager"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';

interface OverlayContent {
  title: string;
  subtitle: string;
}

interface DecorationConfig {
  type: 'circle' | 'line' | 'square';
  position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
  size: 'sm' | 'md' | 'lg';
  color?: 'vusa-red' | 'vusa-yellow' | 'zinc';
  opacity?: number;
  rotation?: boolean;
}

interface Props {
  title: string;
  description: string;
  imageSrc: string;
  imageAlt: string;
  objectPosition?: string;
  overlayContent?: OverlayContent;
  textLeft?: boolean;
  imageDecorations?: DecorationConfig[];
}

withDefaults(defineProps<Props>(), {
  textLeft: true,
  objectPosition: '40% 65%',
  imageDecorations: () => [
    { type: 'line', position: 'top-right', size: 'md', color: 'vusa-red', opacity: 60 },
    { type: 'square', position: 'top-left', size: 'md', color: 'vusa-yellow', rotation: true }
  ],
});
</script>
