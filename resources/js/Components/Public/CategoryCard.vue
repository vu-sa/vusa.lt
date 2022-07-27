<template>
  <div
    v-if="image"
    class="h-full w-full rounded-2xl bg-cover object-cover grayscale transition hover:shadow-lg hover:grayscale-0"
    :style="backgroundImageStyle"
  >
    <div
      class="flex h-full w-full flex-col justify-center transition hover:backdrop-brightness-125"
    >
      <p
        class="text-center text-2xl font-bold text-white sm:text-3xl md:text-4xl"
        style="text-shadow: 3px 3px 3px #111111bb"
      >
        {{ title }}
      </p>
      <slot></slot>
    </div>
  </div>
  <div v-else>
    <p
      class="w-full text-center text-2xl font-bold duration-500 hover:text-vusa-red sm:text-3xl md:text-4xl"
    >
      {{ title }}
    </p>
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
const props = defineProps<{
  title: string | null;
  image: string;
}>();

const backgroundImageStyle = computed(() => {
  return {
    "background-image": `linear-gradient(0deg, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.3)), url(${props.image})`,
  };
});
</script>
