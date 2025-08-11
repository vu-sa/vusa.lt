<template>
  <div class="p-4">
    <div class="grid lg:grid-cols-2 gap-8 md:gap-12 items-center bg-white dark:bg-zinc-800 rounded-2xl p-8 md:p-12 shadow-sm border border-zinc-100 dark:border-zinc-700">
      <div :class="['space-y-4 md:space-y-6', imageLeft ? 'order-1 lg:order-2' : 'order-2 lg:order-1']">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-zinc-100 dark:bg-zinc-700 rounded-full text-sm text-zinc-600 dark:text-zinc-400">
          <component :is="icon" class="w-4 h-4" />
          {{ badge }}
        </div>
        
        <!-- Title -->
        <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
          {{ title }}
        </h3>
        
        <!-- Description -->
        <p class="text-sm sm:text-base md:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
          {{ description }}
        </p>
      </div>
      
      <div :class="['relative', imageLeft ? 'order-2 lg:order-1' : 'order-1 lg:order-2']">
        <ImageWithDecorations
          :src="imageSrc"
          :alt="imageAlt"
          height-class="h-64 md:h-80"
          :decorations="decorations"
          loading="lazy"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
interface DecorationConfig {
  type: 'circle' | 'line' | 'square';
  position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
  size: 'sm' | 'md' | 'lg';
  color?: 'vusa-red' | 'vusa-yellow' | 'zinc';
  opacity?: number;
  rotation?: boolean;
}

interface Props {
  icon: any; // Vue component
  badge: string;
  title: string;
  description: string;
  imageSrc: string;
  imageAlt: string;
  imageLeft?: boolean;
  decorations?: DecorationConfig[];
}

withDefaults(defineProps<Props>(), {
  imageLeft: false,
  decorations: () => [
    { type: 'line', position: 'top-left', size: 'md', color: 'zinc', opacity: 60 },
    { type: 'square', position: 'bottom-right', size: 'lg', color: 'zinc', rotation: true }
  ],
});
</script>
