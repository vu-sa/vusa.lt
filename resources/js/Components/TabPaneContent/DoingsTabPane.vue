<template>
  <div class="grid grid-cols-4 gap-x-4">
    <DoingCard
      v-for="doing in doings"
      :key="doing.id"
      :doing="doing"
    ></DoingCard>
  </div>
</template>

<script setup lang="tsx">
import DoingCard from "../Cards/DoingCard.vue";

defineProps<{
  doings: App.Models.Doing[];
}>();
</script>
