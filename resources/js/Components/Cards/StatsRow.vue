<template>
  <div class="relative">
    <!-- Horizontal scroll container on mobile, grid on larger screens -->
    <div
      :class="[
        // Mobile: horizontal scroll
        'flex gap-4 overflow-x-auto pb-2 -mx-4 px-4',
        'snap-x snap-mandatory scroll-smooth',
        // Hide scrollbar on mobile while keeping functionality
        'scrollbar-none',
        // Desktop: responsive grid
        'md:grid md:overflow-visible md:mx-0 md:px-0 md:pb-0',
        gridColsClass,
      ]"
    >
      <slot />
    </div>

    <!-- Scroll fade indicators (mobile only) -->
    <div
      v-if="showScrollIndicators"
      :class="[
        'pointer-events-none absolute inset-y-0 left-0 w-8',
        'bg-gradient-to-r from-white dark:from-zinc-950 to-transparent',
        'md:hidden',
        'transition-opacity duration-300',
        scrollPosition > 10 ? 'opacity-100' : 'opacity-0',
      ]"
    />
    <div
      v-if="showScrollIndicators"
      :class="[
        'pointer-events-none absolute inset-y-0 right-0 w-8',
        'bg-gradient-to-l from-white dark:from-zinc-950 to-transparent',
        'md:hidden',
        'transition-opacity duration-300',
        !isAtEnd ? 'opacity-100' : 'opacity-0',
      ]"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'

interface Props {
  columns?: 2 | 3 | 4 | 5
  showScrollIndicators?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  columns: 4,
  showScrollIndicators: true,
})

// Grid column classes based on props
const gridColsClass = computed(() => {
  const colClasses: Record<number, string> = {
    2: 'md:grid-cols-2',
    3: 'md:grid-cols-3',
    4: 'md:grid-cols-2 lg:grid-cols-4',
    5: 'md:grid-cols-3 lg:grid-cols-5',
  }
  return colClasses[props.columns] || 'md:grid-cols-4'
})

// Scroll position tracking for fade indicators
const scrollPosition = ref(0)
const isAtEnd = ref(false)

let scrollContainer: HTMLElement | null = null

const handleScroll = (e: Event) => {
  const target = e.target as HTMLElement
  scrollPosition.value = target.scrollLeft
  isAtEnd.value = target.scrollLeft + target.clientWidth >= target.scrollWidth - 10
}

onMounted(() => {
  scrollContainer = document.querySelector('.snap-x')
  scrollContainer?.addEventListener('scroll', handleScroll, { passive: true })
})

onUnmounted(() => {
  scrollContainer?.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped>
/* Hide scrollbar but keep scroll functionality */
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
</style>
