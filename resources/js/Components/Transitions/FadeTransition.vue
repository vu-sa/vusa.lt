<template>
  <!-- eslint-disable-next-line vue/require-toggle-inside-transition -->
  <Transition name="fade"><slot></slot></Transition>
</template>

<script setup lang="ts"></script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.12s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
