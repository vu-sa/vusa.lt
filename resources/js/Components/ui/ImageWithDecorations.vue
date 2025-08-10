<template>
  <div class="relative">
    <img 
      :src="src" 
      :alt="alt"
      :loading="loading"
      :style="objectPosition ? `object-position: ${objectPosition}` : ''"
      :class="[
        'w-full object-cover rounded-xl shadow-lg transition-transform duration-300',
        heightClass,
        { 'group-hover:scale-105': hoverScale }
      ]"
    >
    
    <!-- Decorative elements -->
    <DecorativeElement
      v-for="decoration in decorations"
      :key="`${decoration.type}-${decoration.position}`"
      :type="decoration.type"
      :position="decoration.position"
      :size="decoration.size"
      :color="decoration.color"
      :opacity="decoration.opacity"
      :rotation="decoration.rotation"
    />
    
    <!-- Icon overlay -->
    <div
      v-if="icon"
      class="absolute top-4 right-4 w-10 h-10 bg-white/90 dark:bg-zinc-800/90 rounded-full flex items-center justify-center shadow-sm"
    >
      <component :is="icon" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
    </div>
    
    <!-- Overlay content (like the overlapping text card in hero) -->
    <div 
      v-if="overlayContent"
      :class="[
        'absolute rounded-xl shadow-xl border border-zinc-100 dark:border-zinc-700',
        overlayPosition,
        overlaySize,
        overlayStyle === 'backdrop' 
          ? 'bg-white/95 dark:bg-zinc-800/95 backdrop-blur-sm p-3' 
          : 'bg-white dark:bg-zinc-800 p-2 sm:p-3 md:p-4 2xl:p-6'
      ]"
    >
      <div class="flex items-center space-x-1 sm:space-x-2 md:space-x-3 mb-1 sm:mb-2">
        <div class="w-2 h-2 sm:w-2.5 sm:h-2.5 md:w-3 md:h-3 bg-vusa-yellow rounded-full" />
        <span :class="[
          'font-medium text-zinc-600 dark:text-zinc-400',
          overlayStyle === 'backdrop' ? 'text-xs' : 'text-xs sm:text-sm'
        ]">
          {{ overlayContent.title }}
        </span>
      </div>
      <p :class="[
        'text-zinc-500 dark:text-zinc-500',
        overlayStyle === 'backdrop' ? 'text-xs' : 'text-xs sm:text-sm'
      ]">
        {{ overlayContent.subtitle }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import DecorativeElement from '@/Components/ui/DecorativeElement.vue';

interface DecorationConfig {
  type: 'circle' | 'line' | 'square';
  position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
  size: 'sm' | 'md' | 'lg';
  color?: 'vusa-red' | 'vusa-yellow' | 'zinc';
  opacity?: number;
  rotation?: boolean;
}

interface OverlayContent {
  title: string;
  subtitle: string;
}

interface Props {
  src: string;
  alt: string;
  loading?: 'lazy' | 'eager';
  height?: 'sm' | 'md' | 'lg' | 'xl' | 'custom';
  heightClass?: string; // For custom heights
  decorations?: DecorationConfig[];
  icon?: any; // Vue component
  overlayContent?: OverlayContent;
  overlayPosition?: string;
  overlaySize?: string;
  overlayStyle?: 'default' | 'backdrop';
  objectPosition?: string;
  hoverScale?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: 'lazy',
  height: 'md',
  decorations: () => [],
  hoverScale: false,
  overlayPosition: '-bottom-3 -left-3 sm:-bottom-4 sm:-left-4 md:-bottom-6 md:-left-6 2xl:-bottom-8 2xl:-left-8',
  overlaySize: 'max-w-[200px] sm:max-w-xs',
  overlayStyle: 'default',
});

const heightClass = computed(() => {
  if (props.heightClass) return props.heightClass;
  
  const heights = {
    'sm': 'h-32 md:h-40',
    'md': 'h-40 md:h-52',
    'lg': 'h-52 md:h-64',
    'xl': 'h-64 md:h-80',
    'custom': ''
  };
  return heights[props.height];
});
</script>
