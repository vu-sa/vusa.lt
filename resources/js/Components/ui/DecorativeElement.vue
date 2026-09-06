<template>
  <div
    :class="[
      'absolute',
      positionClasses,
      sizeClasses,
      colorClasses,
      shapeClasses,
      opacityClasses,
    ]"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  type: 'circle' | 'line' | 'square';
  position: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' | 'center';
  size: 'sm' | 'md' | 'lg';
}

const props = defineProps<Props>();

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

// Fixed brand treatment — colour is no longer an authorable option (see
// 2026_09_04_HHMMSS_drop_drifted_block_colour_settings.php), so every decorative
// accent uses the same token regardless of type.
const colorClasses = computed(() => (props.type === 'square'
  ? 'border-2 border-brand bg-card/80'
  : 'bg-brand'));

const shapeClasses = computed(() => {
  const shapes = {
    circle: 'rounded-full',
    line: 'rounded-full',
    square: 'rounded-lg',
  };
  return shapes[props.type];
});

const opacityClasses = computed(() => (props.type === 'square' ? '' : 'opacity-60'));
</script>
