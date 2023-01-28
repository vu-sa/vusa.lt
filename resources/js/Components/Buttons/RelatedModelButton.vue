<template>
  <NButton round :type="active ? 'primary' : 'default'"
    ><template #icon
      ><NIcon v-if="icon" :size="16" :component="icon"></NIcon
    ></template>
    <div class="inline-flex items-center gap-2">
      <span>{{ name }}</span>
      <NTag v-if="count !== undefined" :bordered="false" round size="tiny">
        <span class="text-xs text-zinc-800 dark:text-zinc-300">
          {{ count }}
        </span>
      </NTag>
    </div>
  </NButton>
</template>

<script setup lang="tsx">
import { NButton, NIcon, NTag } from "naive-ui";
import type { Component } from "vue";

defineProps<{
  name: string;
  icon?: Component;
  count?: number | string;
  active?: boolean;
}>();
</script>
