<template>
  <div class="flex gap-2">
    <AsideHeaderLink aside-header-route="pages.index"
      >Puslapiai</AsideHeaderLink
    >
    <AsideHeaderLink aside-header-route="news.index">Naujienos</AsideHeaderLink>
    <AsideHeaderLink aside-header-route="mainPage.index"
      >Pradinis puslapis</AsideHeaderLink
    >
    <AsideHeaderLink aside-header-route="banners.index"
      >Baneriai</AsideHeaderLink
    >
  </div>
</template>

<script setup lang="ts">
import AsideHeaderLink from "@/Components/Admin/Nav/AsideHeaderLink.vue";
</script>
