<template>
  <div :class="['mb-12 md:mb-16', align === 'start' ? 'text-left' : 'text-center']">
    <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-zinc-100 mb-4">
      {{ title }}
    </h2>
    <p v-if="subtitle" :class="['text-base sm:text-lg text-zinc-600 dark:text-zinc-400 mb-6 max-w-3xl', align === 'start' ? '' : 'mx-auto']">
      {{ subtitle }}
    </p>
    <div :class="['w-16 h-1 bg-zinc-400 dark:bg-zinc-500 rounded-full', align === 'start' ? '' : 'mx-auto']" />
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string;
  subtitle?: string;
  align?: 'center' | 'start';
}

withDefaults(defineProps<Props>(), {
  align: 'center',
});
</script>
