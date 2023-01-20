<template>
  <button
    class="flex max-w-sm flex-col items-center justify-center gap-2 rounded-sm border px-2 py-4 text-zinc-500 duration-200 hover:shadow-inner dark:border-zinc-700 dark:shadow-zinc-900"
    @click="$emit('click')"
  >
    <NIcon size="40" :depth="5" :component="icon"></NIcon>
    <span class="text-center"><slot>Sukurti naują?</slot></span>
  </button>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import type { Component } from "vue";

defineEmits(["click"]);

defineProps<{
  icon: Component;
}>();
</script>
