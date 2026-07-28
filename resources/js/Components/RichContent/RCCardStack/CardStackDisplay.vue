<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :background="element.options?.background ?? 'muted'" :padding="element.options?.padding ?? 'lg'"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <div class="relative max-w-lg mx-auto">
      <!-- Stack of Cards -->
      <div class="relative h-80 perspective-1000">
        <div
          v-for="(card, index) in element.json_content"
          :key="index"
          class="absolute inset-0 transition-all duration-700 ease-in-out cursor-pointer transform-gpu"
          :style="getCardStyle(index)"
          @click="handleCardClick"
        >
          <div class="h-full p-6 md:p-8 bg-gradient-to-br from-zinc-50 to-zinc-100/50 ring-1 ring-zinc-200/60 hover:ring-zinc-300 dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
            <h3 class="text-xl sm:text-xl font-semibold mb-3 md:mb-4 text-zinc-900 dark:text-zinc-100">
              {{ card.title }}
            </h3>
            <p class="text-[14.5px] sm:text-base text-zinc-600 dark:text-zinc-400 leading-relaxed">
              {{ card.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Navigation Indicators -->
      <div class="flex justify-center mt-8 space-x-2">
        <button
          v-for="(card, index) in element.json_content"
          :key="index"
          class="w-3 h-3 rounded-full transition-all duration-300"
          :class="index === currentCardIndex ? 'bg-zinc-400 dark:bg-zinc-500' : 'bg-zinc-200 dark:bg-zinc-700'"
          @click="handleIndicatorClick(index)"
        />
      </div>

      <!-- Control Hint -->
      <div v-if="element.options?.hintText" class="text-center mt-4">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
          {{ element.options.hintText }}
        </p>
      </div>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import type { CardStack } from '@/Types/contentParts';
import RCSection from '../RCSection.vue';

const { element } = defineProps<{
  element: CardStack;
  anchorId?: number | null;
}>();

// Card stack state
const currentCardIndex = ref(0);
const isRotating = ref(false);
let autoplayInterval: NodeJS.Timeout | null = null;

// Function to get card styles for stack effect
const getCardStyle = (index: number) => {
  const totalCards = element.json_content.length;
  if (totalCards === 0) return { zIndex: 0, transform: '', opacity: 0, transformOrigin: 'center center' };
  const relativeIndex = (index - currentCardIndex.value + totalCards) % totalCards;

  // Stack configuration
  const baseZIndex = 10;
  const rotateStep = 4; // degrees
  const translateStep = 8; // pixels
  const scaleStep = 0.05;
  const opacityStep = 0.15;

  const zIndex = baseZIndex - relativeIndex;
  const rotate = relativeIndex * rotateStep;
  const translateY = relativeIndex * translateStep;
  const scale = 1 - (relativeIndex * scaleStep);
  const opacity = 1 - (relativeIndex * opacityStep);

  return {
    zIndex,
    transform: `
      translateY(${translateY}px)
      scale(${scale})
      rotateZ(${rotate}deg)
    `,
    opacity: Math.max(0.3, opacity),
    transformOrigin: 'center center',
  };
};

// Function to rotate cards (move current to back)
const rotateCards = () => {
  if (isRotating.value || element.json_content.length === 0) return;

  isRotating.value = true;
  currentCardIndex.value = (currentCardIndex.value + 1) % element.json_content.length;

  setTimeout(() => {
    isRotating.value = false;
  }, 700); // Match transition duration
};

// Function to set specific card as current
const setCurrentCard = (index: number) => {
  if (isRotating.value || index === currentCardIndex.value) return;

  isRotating.value = true;
  currentCardIndex.value = index;

  setTimeout(() => {
    isRotating.value = false;
  }, 700);
};

// Autoplay functionality
const startAutoplay = () => {
  if (!element.options?.autoplay || autoplayInterval) return;

  autoplayInterval = setInterval(() => {
    if (!isRotating.value) {
      rotateCards();
    }
  }, element.options?.autoplayDelay || 5000);
};

const stopAutoplay = () => {
  if (autoplayInterval) {
    clearInterval(autoplayInterval);
    autoplayInterval = null;
  }
};

const restartAutoplay = () => {
  stopAutoplay();
  if (element.options?.autoplay) {
    startAutoplay();
  }
};

// Handle user interactions - pause autoplay temporarily
const handleUserInteraction = (callback: () => void) => {
  stopAutoplay();
  callback();

  // Restart autoplay after user interaction
  if (element.options?.autoplay) {
    setTimeout(startAutoplay, element.options?.autoplayDelay || 5000);
  }
};

// Override click handlers to include autoplay management
const handleCardClick = () => {
  handleUserInteraction(rotateCards);
};

const handleIndicatorClick = (index: number) => {
  handleUserInteraction(() => setCurrentCard(index));
};

// Lifecycle
onMounted(() => {
  if (element.options?.autoplay) {
    startAutoplay();
  }
});

onUnmounted(() => {
  stopAutoplay();
});
</script>

<style scoped>
.perspective-1000 {
  perspective: 1000px;
}

.transform-gpu {
  transform-style: preserve-3d;
}
</style>