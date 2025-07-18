<template>
  <div ref="containerRef" :class="containerClass">
    <!-- Show loading skeleton until component becomes visible -->
    <div v-if="!isVisible" class="w-full flex items-center justify-center py-8">
      <div class="flex flex-col items-center gap-5">
        <Skeleton class="h-10 w-10 rounded-full" />
        <div class="space-y-3">
          <Skeleton class="h-3 w-40" />
          <Skeleton class="h-2 w-28" />
          <Skeleton class="h-2 w-24" />
        </div>
      </div>
    </div>
    
    <!-- Render actual component when visible -->
    <Suspense v-else>
      <template #default>
        <slot :is-visible="isVisible" />
      </template>
      <template #fallback>
        <div class="w-full flex items-center justify-center py-8">
          <div class="flex flex-col items-center gap-5">
            <Skeleton class="h-10 w-10 rounded-full" />
            <div class="space-y-3">
              <Skeleton class="h-3 w-40" />
              <Skeleton class="h-2 w-28" />
            </div>
          </div>
        </div>
      </template>
    </Suspense>
  </div>
</template>

<script setup lang="ts">
import { useIntersectionObserver } from '@vueuse/core';
import { ref } from 'vue';
import { Skeleton } from '@/Components/ui/skeleton';

interface Props {
  rootMargin?: string;
  threshold?: number;
  containerClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  rootMargin: '100px',
  threshold: 0.1,
  containerClass: ''
});

const emit = defineEmits<{
  visible: []
}>();

const containerRef = ref<HTMLElement>();
const isVisible = ref(false);

// Use VueUse's intersection observer with once behavior
useIntersectionObserver(
  containerRef,
  ([{ isIntersecting }]) => {
    if (isIntersecting && !isVisible.value) {
      isVisible.value = true;
      emit('visible');
    }
  },
  {
    rootMargin: props.rootMargin,
    threshold: props.threshold,
  }
);
</script>