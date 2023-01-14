<template>
  <div
    role="button"
    class="mx-1 my-2 flex flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-zinc-700 p-2 text-zinc-500 duration-200 hover:shadow-inner dark:bg-zinc-900/60"
    @click="$emit('click')"
  >
    <NIcon size="24" :depth="5" :component="icon"></NIcon>
    <span><slot></slot></span>
  </div>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import type { Component } from "vue";

defineEmits(["click"]);

defineProps<{
  icon: Component;
}>();
</script>
