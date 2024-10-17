<template>
  <SmartLink v-if="mainPageLink?.link" :href="mainPageLink?.link" class="plain inline-flex gap-1.5 duration-200" :class="{
    'font-bold text-vusa-red hover:text-vusa-red-secondary': mainPageLink?.is_important,
    'text-zinc-700 hover:text-vusa-red dark:text-zinc-300': !mainPageLink?.is_important,
  }">
    <!-- translate-y-0 is needed, because the icon is "jumpy", because of group-hover in parent -->
    <Icon v-if="mainPageLink.icon" :icon="`fluent:${mainPageLink.icon}`" class="size-4 translate-y-0" />
    {{ mainPageLink?.text }}
  </SmartLink>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue";
import SmartLink from "../SmartLink.vue";

defineProps<{
  mainPageLink: App.Entities.MainPage | null;
}>();
</script>
