<template>
  <div class="grid gap-x-12 lg:grid-cols-6">
    <div class="lg:col-span-2">
      <h4 class="tracking-tighter"><slot name="title"></slot></h4>
      <div class="mb-6 flex flex-col text-xs text-zinc-500 dark:text-zinc-400">
        <slot name="description"></slot>
      </div>
    </div>
    <div class="lg:col-span-4">
      <slot />
    </div>
    <div class="lg:col-span-6"><NDivider v-if="!noDivider" /></div>
  </div>
</template>

<script setup lang="tsx">
import { NDivider } from "naive-ui";

defineProps<{
  noDivider?: boolean;
}>();
</script>
