<template>
  <span ref="target">{{ displayNumber }}{{ showPlus ? '+' : '' }}</span>
</template>

<script setup lang="ts">
import { computed, ref, useTemplateRef, watch } from 'vue';
import { useIntersectionObserver, useTransition, TransitionPresets } from '@vueuse/core';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

const props = defineProps<{
  endNumber: number;
  showPlus?: boolean;
}>();

const target = useTemplateRef('target');
const targetIsVisible = ref(false);
const source = ref(0);
const { isReducedMotion } = useAccessibilityPreferences();

const output = useTransition(source, {
  duration: 2000,
  transition: TransitionPresets.easeOutCubic,
  disabled: isReducedMotion,
});

const displayNumber = computed(() => Math.round(isReducedMotion.value ? props.endNumber : output.value));

// run the animation only once on intersection
const { stop } = useIntersectionObserver(target, ([{ isIntersecting }]) => {
  if (isIntersecting) {
    stop();
    targetIsVisible.value = true;
  }
});

watch([targetIsVisible, () => props.endNumber], ([isVisible, endNumber]) => {
  if (isVisible) {
    source.value = endNumber;
  }
});
</script>
