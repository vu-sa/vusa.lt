<template>
  <div class="text-center mb-12 md:mb-16">
    <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-zinc-100 mb-4">
      {{ title }}
    </h2>
    <p v-if="subtitle" class="text-base sm:text-lg text-zinc-600 dark:text-zinc-400 mb-6 max-w-3xl mx-auto">
      {{ subtitle }}
    </p>
    <div class="w-16 h-1 bg-zinc-400 dark:bg-zinc-500 mx-auto rounded-full" />
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string;
  subtitle?: string;
}

defineProps<Props>();
</script>
