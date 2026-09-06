<template>
  <li class="animate-pulse py-4 sm:py-5">
    <div class="flex items-center gap-3 sm:gap-4">
      <!-- DatePlate skeleton -->
      <div class="h-14 w-16 border border-border bg-secondary/60 shrink-0" />

      <div class="min-w-0 flex-1 space-y-2">
        <div class="h-4 w-40 max-w-full bg-secondary" />
        <div class="h-3 w-28 bg-secondary/70" />
      </div>

      <!-- Indicators & arrow -->
      <div class="flex items-center gap-3 shrink-0">
        <div class="h-4 w-16 bg-secondary/70" />
        <div class="hidden size-4 bg-secondary/40 shrink-0 sm:block" />
      </div>
    </div>
  </li>
</template>

<script setup lang="ts">
interface Props {
  viewMode?: string;
}

withDefaults(defineProps<Props>(), {
  viewMode: undefined,
});
</script>
