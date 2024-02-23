<template>
  <section class="gap-3 flex flex-col">
    <span class="inline-flex items-center gap-2 text-xl font-bold">
      <NIcon :component="icon" />
      {{ title }} <NTag size="tiny">{{ content?.id ? `#${content?.id}` : 'Nauja' }}</NTag>
    </span>
    <slot />
  </section>
</template>

<script setup lang="ts">
import { NIcon, NTag } from 'naive-ui';
import type { Component } from 'vue';

const props = defineProps<{
  icon: Component;
  id?: number;
  title: string;
}>()
</script>
