<template>
  <NButton
    ghost
    class="h-full max-w-sm flex-col justify-center gap-4 rounded-sm border px-2 py-4 text-zinc-500 duration-200 hover:shadow-inner dark:border-zinc-700 dark:shadow-zinc-900"
    @click="$emit('click')"
  >
    <template #icon><NIcon size="40" :component="icon"></NIcon></template>
    <span class="text-center"
      ><slot>{{ $t("Sukurti naują") }}?</slot></span
    >
  </NButton>
</template>

<script setup lang="tsx">
import { NButton, NIcon } from "naive-ui";
import type { Component } from "vue";

defineEmits(["click"]);

defineProps<{
  icon: Component;
}>();
</script>
