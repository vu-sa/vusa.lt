<template>
  <article class="relative">
    <div v-if="hasMiniContent"
      class="absolute -top-2 -right-1 rounded-xl border border-gray-100 bg-white px-2 text-gray-500 shadow-xs duration-200 line-clamp-1 hover:shadow-md">
      <slot name="mini" />
    </div>
    <slot name="image" />
    <div v-if="hasBelowCard" class="mt-1 flex items-center text-sm text-gray-600 dark:text-zinc-200">
      <slot name="below-card" />
    </div>
    <p class="mt-1 font-bold text-xl leading-tight text-gray-800 line-clamp-2 dark:text-zinc-50">
      <slot />
    </p>
  </article>
</template>

<script setup lang="ts">
defineProps<{
  hasMiniContent: boolean;
  hasBelowCard: boolean;
}>();
</script>
