<template>
  <!-- Has title, subtitle, button with text and link, backgroundMedia and rightMedia -->
  <!-- Also can be left or center aligned -->
  <div class="@container/grid full-bleed">
    <section class="relative grid @max-5xl/grid:grid-rows-[420px_300px] @5xl/grid:grid-cols-2 h-180 @5xl/grid:h-144"
      :class="{ '-mt-36 2xl:-mt-34': isFirstElement }">
      <div class="z-10 flex @max-5xl/grid:px-16 @5xl/grid:pl-32 flex-col @max-5xl/grid:items-center mt-auto mb-16">
        <div class="text-5xl text-center @5xl/grid:text-left font-bold text-white mb-4"
          v-html="element.json_content.title" />
        <div class="text-lg text-zinc-200 mb-4" v-html="element.json_content.subtitle" />
        <SmartLink v-if="element.json_content.buttonLink" :href="element.json_content.buttonLink" class="mt-4 w-fit">
          <NButton round :color="buttonColor" size="large" strong>
            <span class="text-black">{{ element.json_content.buttonText }}</span>
          </NButton>
        </SmartLink>
      </div>
      <img :src="element.json_content.backgroundMedia" :class="[{ 'blur-[1px]': element.options?.backgroundBlur }]"
        class="object-cover w-full h-full absolute inset-0 flex items-center justify-center 2xl:rounded-lg">
      <div v-if="element.options?.backgroundBlur"
        class="w-full h-full absolute inset-0 bg-black opacity-50 2xl:rounded-lg" />
      <div class="z-10 flex items-center justify-center @max-5xl/grid:-order-1">
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
import { computed } from 'vue';

const { element } = defineProps<{
  element: Hero;
  isFirstElement: boolean;
}>();

const buttonColor = computed(() => {
  switch (element.options?.buttonColor) {
    case 'red':
      return '#bd2835';
    case 'yellow':
      return '#fbb01b';
    case 'zinc':
      return '#1f1f1f';
    case 'white':
      return '#ffffff';
    default:
      return '#bd2835';
  }
});
</script>
