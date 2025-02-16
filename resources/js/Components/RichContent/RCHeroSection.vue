<template>
  <!-- Has title, subtitle, button with text and link, backgroundMedia and rightMedia -->
  <!-- Also can be left or center aligned -->
  <section class="relative grid grid-cols-2 w-full h-[70vh] full-bleed">
    <div class="relative z-10 col-span-2 flex justify-center px-32 flex-col">
      <h1 class="text-5xl font-bold text-white">
        {{ element.json_content.title }}
      </h1>
      <p class="text-xl text-zinc-100">
        {{ element.json_content.subtitle }}
      </p>
      <SmartLink v-if="element.json_content.buttonLink" :href="element.json_content.buttonLink" class="mt-4">
        <NButton round type="primary" size="large">
          {{ element.json_content.buttonText }}
        </NButton>
      </SmartLink>
    </div>
    <div class="absolute inset-0 flex items-center justify-center">
      <img :src="element.json_content.backgroundMedia" class="object-cover w-full h-full">
    </div>
    <div class="absolute inset-0 bg-zinc-950/70 backdrop-blur-[1px]" />
    <!-- <div class="absolute top-0 h-6 w-full bg-linear-to-b from-white to-transparent dark:from-zinc-900" />
    <div class="absolute bottom-0 h-5 w-full bg-linear-to-b from-transparent to-white dark:to-zinc-900" /> -->
    <!-- <div class="absolute inset-0 flex items-center justify-center">
      <img :src="element.json_content.rightMedia" class="object-cover w-full h-full" />
</div>-->
  </section>
</template>

<script setup lang="ts">
import type { Hero } from '@/Types/contentParts';
import SmartLink from '../Public/SmartLink.vue';

defineProps<{
  element: Hero;
}>();
</script>
