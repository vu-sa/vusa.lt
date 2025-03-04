<template>
  <!-- Has title, subtitle, button with text and link, backgroundMedia and rightMedia -->
  <!-- Also can be left or center aligned -->
  <div class="@container/grid full-bleed">
    <section class="relative grid grid-cols-1 @5xl/grid:grid-cols-2 h-144"
      :class="{ '-mt-36 2xl:-mt-34': isFirstElement }">
      <div class="z-10 flex @max-5xl/grid:px-16 @5xl/grid:pl-32 flex-col @max-5xl/grid:items-center mt-auto mb-16">
        <div class="text-5xl font-bold text-white mb-4" v-html="element.json_content.title" />
        <div class="text-lg text-zinc-200 mb-4" v-html="element.json_content.subtitle" />
        <SmartLink v-if="element.json_content.buttonLink" :href="element.json_content.buttonLink" class="mt-4">
          <NButton round type="warning" size="large" strong>
            <span class="text-black">{{ element.json_content.buttonText }}</span>
          </NButton>
        </SmartLink>
      </div>
      <img :src="element.json_content.backgroundMedia" :class="[{ 'blur-[1px]': element.options?.backgroundBlur }]"
        class="object-cover w-full h-full absolute inset-0 flex items-center justify-center 2xl:rounded-md">
      <div v-if="element.options?.backgroundBlur" class="w-full h-full absolute inset-0 bg-black opacity-50" />
      <div class="z-10 flex items-center justify-center overflow-hidden w-full h-full @max-5xl/grid:hidden">
        <img class="h-auto max-w-full object-contain" :src="element.json_content.rightMedia">
      </div>
      <!-- <div class="absolute top-0 h-6 w-full bg-linear-to-b from-white to-transparent dark:from-zinc-900" />
    <div class="absolute bottom-0 h-5 w-full bg-linear-to-b from-transparent to-white dark:to-zinc-900" /> -->
      <!-- <div class="absolute inset-0 flex items-center justify-center">
      <img :src="element.json_content.rightMedia" class="object-cover w-full h-full" />
</div>-->
    </section>
  </div>
</template>

<script setup lang="ts">
import type { Hero } from '@/Types/contentParts';
import SmartLink from '../../Public/SmartLink.vue';

defineProps<{
  element: Hero;
  isFirstElement: boolean;
}>();
</script>
