<template>
  <TransitionGroup name="stagger" appear @before-enter="setStaggerIndex">
    <slot />
  </TransitionGroup>
</template>

<script setup lang="ts">
function setStaggerIndex(el: Element) {
  const index = (el as HTMLElement).dataset.index || '0';
  (el as HTMLElement).style.setProperty('--stagger-index', index);
}
</script>

<style>
.stagger-enter-active {
  transition: opacity 0.3s ease-out;
  /* Accelerating stagger: delay = index * 0.3 / (index / 5 + 1) */
  transition-delay: calc(var(--stagger-index, 0) * 300ms / (var(--stagger-index, 0) / 5 + 1));
}

.stagger-enter-from {
  opacity: 0;
}

.stagger-leave-active {
  transition: opacity 0.2s ease-in;
}

.stagger-leave-to {
  opacity: 0;
}
</style>
