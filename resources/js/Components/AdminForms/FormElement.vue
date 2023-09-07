<template>
  <div class="grid gap-x-12 lg:grid-cols-6">
    <div class="lg:col-span-2">
      <h4 class="inline-flex items-center gap-2 tracking-normal">
        <NIcon v-if="icon" :component="icon" />
        <slot name="title" />
      </h4>
      <div class="mb-6 flex flex-col text-xs text-zinc-500 dark:text-zinc-400">
        <slot name="description" />
      </div>
    </div>
    <div class="lg:col-span-4">
      <slot />
    </div>
    <div class="lg:col-span-6"><NDivider v-if="!noDivider" /></div>
  </div>
</template>

<script setup lang="tsx">
import { NDivider, NIcon } from "naive-ui";
import type { Component } from "vue";

defineProps<{
  noDivider?: boolean;
  icon?: Component;
}>();
</script>
