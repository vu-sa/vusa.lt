<template>
  <TransitionGroup name="fade">
    <slot />
  </TransitionGroup>
</template>

<script setup lang="ts"></script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}
</style>
