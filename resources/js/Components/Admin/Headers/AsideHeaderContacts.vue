<template>
  <div class="flex gap-2">
    <AsideHeaderLink aside-header-route="dutyInstitutions.index"
      >Institucijos</AsideHeaderLink
    >
    <AsideHeaderLink aside-header-route="duties.index"
      >Pareigos</AsideHeaderLink
    >
    <AsideHeaderLink aside-header-route="users.index"
      >Kontaktai</AsideHeaderLink
    >
  </div>
</template>

<script setup lang="ts">
import AsideHeaderLink from "@/Components/Admin/Nav/AsideHeaderLink.vue";
</script>
