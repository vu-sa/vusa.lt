<template>
  <!-- Grid View Skeleton -->
  <div
    v-if="viewMode === 'grid'"
    class="flex h-full flex-col border border-border bg-card animate-pulse"
  >
    <!-- Cover skeleton -->
    <div class="aspect-[16/10] w-full bg-secondary" />

    <!-- Logo placeholder -->
    <div class="px-5 -mt-6">
      <div class="size-14 border border-border bg-background" />
    </div>

    <!-- Content skeleton -->
    <div class="flex flex-1 flex-col justify-between space-y-3 px-5 pb-5 pt-4">
      <div class="space-y-2">
        <div class="h-4 bg-secondary/70 w-24" />
        <div class="h-5 bg-secondary w-3/4" />
        <div class="h-3 bg-secondary/60 w-full" />
      </div>

      <div class="flex items-center justify-between border-t border-border pt-4">
        <div class="h-3 bg-secondary/70 w-20" />
        <div class="flex gap-1">
          <div class="size-5 bg-secondary/50" />
          <div class="size-5 bg-secondary/50" />
        </div>
      </div>
    </div>
  </div>

  <!-- List View Skeleton -->
  <li
    v-else
    class="animate-pulse flex items-center gap-3 py-3 sm:gap-4 sm:py-3.5"
  >
    <!-- Logo skeleton -->
    <div class="size-11 border border-border bg-secondary shrink-0" />

    <!-- Text skeleton -->
    <div class="flex-1 min-w-0 space-y-1.5">
      <div class="h-4 bg-secondary w-48 max-w-full" />
      <div class="h-5 bg-secondary/70 w-24" />
    </div>

    <!-- Metadata skeleton -->
    <div class="hidden sm:flex items-center gap-3 shrink-0">
      <div class="h-3 bg-secondary/70 w-12" />
      <div class="h-5 bg-secondary/70 w-16" />
      <div class="size-4 bg-secondary/50" />
    </div>
  </li>
</template>

<script setup lang="ts">
interface Props {
  viewMode?: 'grid' | 'list';
}

withDefaults(defineProps<Props>(), {
  viewMode: 'grid',
});
</script>
