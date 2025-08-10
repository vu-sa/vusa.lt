<template>
  <div class="relative max-w-lg mx-auto">
    <!-- Stack of Cards -->
    <div class="relative h-80 perspective-1000">
      <div 
        v-for="(card, index) in cards" 
        :key="index"
        class="absolute inset-0 transition-all duration-700 ease-in-out cursor-pointer transform-gpu"
        :style="getCardStyle(index)"
        @click="rotateCards"
      >
        <div class="h-full p-6 md:p-8 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
          <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center mb-4 md:mb-6 transition-colors duration-300"
               :class="index === currentCardIndex ? 'bg-zinc-200 dark:bg-zinc-600' : ''">
            <component :is="card.icon" class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
          </div>
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
        v-for="(card, index) in cards" 
        :key="index"
        class="w-3 h-3 rounded-full transition-all duration-300"
        :class="index === currentCardIndex ? 'bg-zinc-400 dark:bg-zinc-500' : 'bg-zinc-200 dark:bg-zinc-700'"
        @click="setCurrentCard(index)"
      />
    </div>
    
    <!-- Control Hint -->
    <div class="text-center mt-4">
      <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ hintText }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface Card {
  icon: any;
  title: string;
  description: string;
}

interface Props {
  cards: Card[];
  autoplay?: boolean;
  autoplayDelay?: number;
  hintText?: string;
}

const props = withDefaults(defineProps<Props>(), {
  autoplay: false,
  autoplayDelay: 4000,
  hintText: 'Click card or indicator'
});

// Card stack state
const currentCardIndex = ref(0);
const isRotating = ref(false);
let autoplayInterval: NodeJS.Timeout | null = null;

// Function to get card styles for stack effect
const getCardStyle = (index: number) => {
  const totalCards = props.cards.length;
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
    zIndex: zIndex,
    transform: `
      translateY(${translateY}px) 
      scale(${scale}) 
      rotateZ(${rotate}deg)
    `,
    opacity: Math.max(0.3, opacity),
    transformOrigin: 'center center'
  };
};

// Function to rotate cards (move current to back)
const rotateCards = () => {
  if (isRotating.value) return;
  
  isRotating.value = true;
  currentCardIndex.value = (currentCardIndex.value + 1) % props.cards.length;
  
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
  if (!props.autoplay || autoplayInterval) return;
  
  autoplayInterval = setInterval(() => {
    if (!isRotating.value) {
      rotateCards();
    }
  }, props.autoplayDelay);
};

const stopAutoplay = () => {
  if (autoplayInterval) {
    clearInterval(autoplayInterval);
    autoplayInterval = null;
  }
};

const restartAutoplay = () => {
  stopAutoplay();
  if (props.autoplay) {
    startAutoplay();
  }
};

// Handle user interactions - pause autoplay temporarily
const handleUserInteraction = (callback: () => void) => {
  stopAutoplay();
  callback();
  
  // Restart autoplay after user interaction
  if (props.autoplay) {
    setTimeout(startAutoplay, props.autoplayDelay);
  }
};

// Lifecycle
onMounted(() => {
  if (props.autoplay) {
    startAutoplay();
  }
});

onUnmounted(() => {
  stopAutoplay();
});

// Override click handlers to include autoplay management
const handleCardClick = () => {
  handleUserInteraction(rotateCards);
};

const handleIndicatorClick = (index: number) => {
  handleUserInteraction(() => setCurrentCard(index));
};
</script>

<style scoped>
.perspective-1000 {
  perspective: 1000px;
}

.transform-gpu {
  transform-style: preserve-3d;
}
</style>
