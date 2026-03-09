<template>
  <span ref="target">{{ displayNumber }}{{ showPlus ? '+' : '' }}</span>
</template>

<script setup lang="ts">
import { computed, ref, useTemplateRef, watch } from "vue";
import { useIntersectionObserver, useTransition, TransitionPresets } from '@vueuse/core'

const props = defineProps<{
  endNumber: number;
  showPlus?: boolean;
}>();

const target = useTemplateRef('target')
const targetIsVisible = ref(false)
const source = ref(0);

const output = useTransition(source, {
  duration: 2000,
  transition: TransitionPresets.easeOutCubic,
});

const displayNumber = computed(() => Math.round(output.value));

// run the animation only once on intersection
const { stop } = useIntersectionObserver(target, ([{ isIntersecting }]) => {
  if (isIntersecting) {
    stop()
    targetIsVisible.value = true
  }
})

watch(targetIsVisible, (isVisible) => {
  if (isVisible) {
    source.value = props.endNumber;
  }
})
</script>
