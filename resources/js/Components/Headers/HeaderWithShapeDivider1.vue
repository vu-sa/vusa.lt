<template>
  <header class="group relative">
    <ShapeDivider1
      :is-theme-dark="isThemeDark"
      class="absolute -top-1 z-10"
    ></ShapeDivider1>
    <ShapeDivider1
      :is-theme-dark="isThemeDark"
      class="absolute bottom-5 z-10 rotate-180 lg:-bottom-1"
    ></ShapeDivider1>
    <div class="relative">
      <img
        :src="imageSrc"
        class="mt-2 h-32 w-full object-cover brightness-50 lg:my-1 lg:h-48"
        style="object-position: 0% 45%"
      />
      <h1 class="relative bottom-16 text-center text-zinc-100 lg:bottom-24">
        <slot />
      </h1>
    </div>
  </header>
</template>

<script setup lang="tsx">
import ShapeDivider1 from "../Public/ShapeDivider1.vue";
defineProps<{
  isThemeDark: boolean;
  imageSrc: string;
}>();
</script>
