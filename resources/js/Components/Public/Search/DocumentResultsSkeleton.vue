<template>
  <!-- List View Skeleton -->
  <li v-if="viewMode === 'list'" class="animate-pulse py-4 sm:py-5">
    <div class="flex items-start gap-3 sm:gap-4">
      <!-- Icon skeleton -->
      <div class="size-10 border border-border bg-secondary shrink-0" />

      <!-- Main content -->
      <div class="flex-1 space-y-3">
        <!-- Title and actions row -->
        <div class="flex items-start justify-between gap-4">
          <div class="h-5 bg-secondary w-3/4" />
          <div class="size-7 bg-secondary shrink-0" />
        </div>

        <!-- Badges -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <div class="h-5 bg-secondary w-20" />
          <div class="h-5 bg-secondary w-16" />
          <div class="h-5 bg-secondary w-12" />
          <div class="h-5 bg-secondary w-14" />
        </div>

        <!-- Summary -->
        <div class="space-y-2">
          <div class="h-3 bg-secondary w-full" />
          <div class="h-3 bg-secondary w-4/5" />
        </div>
      </div>
    </div>
  </li>

  <!-- Compact View Skeleton -->
  <li v-else-if="viewMode === 'compact'" class="animate-pulse py-2.5">
    <div class="flex items-center gap-3 px-3 sm:px-4">
      <!-- Icon skeleton -->
      <div class="size-8 border border-border bg-secondary shrink-0" />

      <!-- Title -->
      <div class="flex-1">
        <div class="h-3.5 bg-secondary w-3/5" />
      </div>

      <!-- Compact metadata -->
      <div class="flex items-center gap-2 shrink-0">
        <!-- Content type badge -->
        <div class="h-4 bg-secondary w-16" />
        <!-- Organization -->
        <div class="h-3 bg-secondary w-12" />
        <!-- Date -->
        <div class="h-3 bg-secondary w-10" />
        <!-- External link icon -->
        <div class="size-3.5 bg-secondary" />
      </div>
    </div>
  </li>
</template>

<script setup lang="ts">
interface Props {
  viewMode?: 'list' | 'compact';
}

withDefaults(defineProps<Props>(), {
  viewMode: 'list',
});
</script>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  .animate-pulse {
    animation: none;
  }
}
</style>
