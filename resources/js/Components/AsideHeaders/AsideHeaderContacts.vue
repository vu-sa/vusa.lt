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
    route: "dutyInstitutions.index",
    title: "Institucijos",
  },
  {
    route: "duties.index",
    title: "Pareigos",
  },
  {
    route: "users.index",
    title: "Kontaktai",
  },
];
</script>
