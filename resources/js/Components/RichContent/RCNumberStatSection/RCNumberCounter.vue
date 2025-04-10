<template>
  <span ref="target">{{ numberRef }}</span>
</template>

<script setup lang="ts">
import { gsap } from "gsap";
import { ref, useTemplateRef, watch } from "vue";
import { useIntersectionObserver } from '@vueuse/core'

const props = defineProps<{
  endNumber: number;
}>();

const target = useTemplateRef('target')
const targetIsVisible = ref(false)
const numberRef = ref(0);

// run the animation only once on intersection

const { stop } = useIntersectionObserver(target, ([{ isIntersecting }]) => {
  if (isIntersecting) {
    stop()
    targetIsVisible.value = true
  }
})

watch(targetIsVisible, (isVisible) => {
  if (isVisible) {
    gsap.to(numberRef, {
      value: props.endNumber,
      duration: 2,
      roundProps: "value",
    });
  }
})
</script>
