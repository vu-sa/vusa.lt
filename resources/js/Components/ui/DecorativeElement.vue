<template>
  <div
    :class="[
      'absolute',
      positionClasses,
      sizeClasses,
      colorClasses,
      shapeClasses,
      opacityClasses,
      rotationClasses
    ]"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  type: 'circle' | 'line' | 'square';
  position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' | 'center';
  size: 'sm' | 'md' | 'lg';
  color?: 'vusa-red' | 'vusa-yellow' | 'zinc';
  opacity?: number;
  rotation?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'zinc',
  opacity: 60,
  rotation: false,
});

const positionClasses = computed(() => {
  const positions = {
    'top-left': 'top-4 left-4',
    'top-right': 'top-4 right-4',
    'bottom-left': 'bottom-4 left-4',
    'bottom-right': 'bottom-4 right-4',
    'center': 'top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2',
  };
  return positions[props.position];
});

const sizeClasses = computed(() => {
  if (props.type === 'line') {
    const sizes = {
      sm: 'w-1.5 h-6',
      md: 'w-2 h-8',
      lg: 'w-3 h-12',
    };
    return sizes[props.size];
  }
  else {
    const sizes = {
      sm: 'w-2 h-2',
      md: 'w-3 h-3',
      lg: 'w-6 h-6',
    };
    return sizes[props.size];
  }
});

const colorClasses = computed(() => {
  const colors = {
    'vusa-red': props.type === 'square'
      ? `border-2 border-vusa-red dark:border-vusa-red bg-white/80 dark:bg-zinc-900/80`
      : 'bg-vusa-red dark:bg-vusa-red',
    'vusa-yellow': props.type === 'square'
      ? `border-2 border-vusa-yellow dark:border-vusa-yellow bg-white/80 dark:bg-zinc-900/80`
      : 'bg-vusa-yellow dark:bg-vusa-yellow',
    'zinc': props.type === 'square'
      ? `border-2 border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800`
      : 'bg-zinc-400 dark:bg-zinc-500',
  };
  return colors[props.color];
});

const shapeClasses = computed(() => {
  const shapes = {
    circle: 'rounded-full',
    line: 'rounded-full',
    square: 'rounded-lg',
  };
  return shapes[props.type];
});

const opacityClasses = computed(() => {
  if (props.type === 'square') return ''; // Squares handle opacity in colorClasses
  return `opacity-${props.opacity}`;
});

const rotationClasses = computed(() => {
  return props.rotation ? 'rotate-45' : '';
});
</script>
