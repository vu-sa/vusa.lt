<template>
  <SmartLink
    v-if="mainPageLink?.link"
    :href="mainPageLink?.link"
    class="text-xs text-zinc-700 dark:text-zinc-300 dark:hover:text-vusa-red"
  >
    {{ mainPageLink?.text }}
  </SmartLink>
</template>

<script setup lang="ts">
import SmartLink from "../SmartLink.vue";

defineProps<{
  mainPageLink: App.Entities.MainPage | null;
}>();
</script>
