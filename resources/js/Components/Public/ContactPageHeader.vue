<template>
  <header class="space-y-4 sm:space-y-6">
    <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 sm:text-4xl">
      {{ title }}
    </h1>
    <div v-if="subtitle" class="space-y-2">
      <div class="w-12 h-1 bg-vusa-red rounded-full" />
      <p class="text-lg text-zinc-600 dark:text-zinc-400">
        {{ subtitle }}
      </p>
    </div>
    <slot />
  </header>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
  subtitle?: string;
}>();
</script>
