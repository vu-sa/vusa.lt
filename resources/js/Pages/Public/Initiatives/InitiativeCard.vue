<template>
  <NCard hoverable>
    <template #cover>
      <img
        :src="initiative.cover"
        alt="initiative image"
        class="h-24 w-full object-cover"
      />
    </template>
    <template #header>
      <h3 class="text-lg/5 font-semibold">{{ initiative.title }}</h3>
    </template>
    <template #header-extra>
      <img
        :src="initiative.logo"
        alt="initiative logo"
        class="h-10 w-10 rounded-full"
      />
    </template>
    <div class="line-clamp-2 text-zinc-500" v-html="initiative.description" />
  </NCard>
</template>

<script setup lang="tsx">
import { NCard } from "naive-ui";
defineProps<{
  initiative: App.Entities.Initiative;
}>();
</script>
