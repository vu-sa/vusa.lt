<template>
  <div class="rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
    <div class="flex">
      <div class="flex-shrink-0">
        <IFluentInfo24Filled class="h-5 w-5 text-blue-400" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
          {{ title }}
        </h3>
        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
}>();
</script>
