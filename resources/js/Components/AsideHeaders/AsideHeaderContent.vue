<template>
  <div class="flex gap-2">
    <AsideHeaderButton
      v-for="option in options"
      :key="option.route"
      :aside-header-route="option.route"
      >{{ option.title }}</AsideHeaderButton
    >
  </div>
</template>

<script setup lang="ts">
import AsideHeaderButton from "@/Components/Buttons/AsideHeaderButton.vue";

const options = [
  {
    route: "pages.index",
    title: "Puslapiai",
  },
  {
    route: "news.index",
    title: "Naujienos",
  },
  {
    route: "mainPage.index",
    title: "Pradinis puslapis",
  },
  {
    route: "banners.index",
    title: "Baneriai",
  },
];
</script>
