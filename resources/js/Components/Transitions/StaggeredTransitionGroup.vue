<template>
  <TransitionGroup :css="false" @before-enter="onBeforeEnter" @enter="onEnter">
    <slot />
  </TransitionGroup>
</template>

<script setup lang="ts">
import { gsap } from "gsap";

function onBeforeEnter(el) {
  el.style.opacity = 0;
}

function onEnter(el, done) {
  gsap.to(el, {
    opacity: 1,
    // Accelerating stagger
    delay: el.dataset.index * 0.3 / (el.dataset.index / 5 + 1),
    onComplete: done
  })
}
</script>
