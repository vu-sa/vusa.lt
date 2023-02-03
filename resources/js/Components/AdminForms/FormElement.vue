<template>
  <div class="col-span-2">
    <h4 class="tracking-tighter"><slot name="title">aaa</slot></h4>
    <p class="mb-6 text-xs text-zinc-500"><slot name="description"></slot></p>
  </div>
  <div class="col-span-4">
    <slot />
  </div>
  <NDivider v-if="!noDivider" class="col-span-full" />
</template>

<script setup lang="tsx">
import { NDivider } from "naive-ui";

defineProps<{
  noDivider?: boolean;
}>();
</script>
