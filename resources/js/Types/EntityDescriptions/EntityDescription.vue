<template>
  <div
    class="grid max-w-2xl grid-cols-[auto_1fr] gap-2 rounded-sm border-zinc-200/80 p-4 dark:border-zinc-700"
    :class="{ border: bordered }"
  >
    <div class="flex w-fit items-center">
      <NIcon size="24" :component="icon"></NIcon>
    </div>
    <h2 class="mb-0 text-xl">{{ title }}</h2>

    <div class="typography col-start-2 text-xs">
      <slot />
    </div>
  </div>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import type { Component } from "vue";

defineProps<{
  bordered?: boolean;
  icon?: Component;
  title?: string;
}>();
</script>
