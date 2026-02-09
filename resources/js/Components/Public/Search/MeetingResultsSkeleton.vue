<template>
  <!-- List View Skeleton - Card layout -->
  <div
    v-if="viewMode === 'list'"
    class="border rounded-lg p-4 bg-card animate-pulse"
  >
    <!-- Date and status -->
    <div class="flex items-start justify-between gap-4 mb-3">
      <div class="flex-1 space-y-2">
        <!-- Date -->
        <div class="h-4 w-32 bg-muted rounded" />
        <!-- Institution name -->
        <div class="h-3 w-40 bg-muted/70 rounded" />
        <!-- Outcome indicators -->
        <div class="flex items-center gap-2 mt-2">
          <div class="h-3 w-20 bg-muted/70 rounded" />
          <div class="h-5 w-16 bg-muted/70 rounded-full" />
        </div>
      </div>
      <!-- Status badge -->
      <div class="h-6 w-20 bg-muted rounded-full shrink-0" />
    </div>

    <!-- View button -->
    <div class="flex justify-end mt-2">
      <div class="h-6 w-24 bg-muted/70 rounded" />
    </div>

    <!-- Agenda items section -->
    <div class="border-t bg-muted/30 -mx-4 px-4 py-3 mt-3">
      <div class="space-y-2">
        <div v-for="i in 2" :key="i" class="space-y-1">
          <div class="h-3 w-full bg-muted/70 rounded" />
          <div class="flex gap-4">
            <div class="h-3 w-24 bg-muted/70 rounded" />
            <div class="h-3 w-24 bg-muted/70 rounded" />
            <div class="h-3 w-24 bg-muted/70 rounded" />
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Compact View Skeleton - Dense card layout -->
  <div
    v-else-if="viewMode === 'compact'"
    class="border rounded-lg p-3 bg-card animate-pulse"
  >
    <!-- Header with date and status -->
    <div class="flex items-start justify-between gap-2 mb-2">
      <div class="h-3 w-24 bg-muted rounded" />
      <div class="h-5 w-16 bg-muted rounded-full" />
    </div>

    <!-- Institution name -->
    <div class="h-3 w-32 bg-muted/70 rounded mb-2" />

    <!-- Meeting title -->
    <div class="space-y-1 mb-2">
      <div class="h-4 w-full bg-muted rounded" />
      <div class="h-4 w-3/4 bg-muted rounded" />
    </div>

    <!-- Stats and indicators -->
    <div class="flex items-center justify-between gap-2">
      <div class="h-3 w-20 bg-muted/70 rounded" />
      <div class="h-5 w-16 bg-muted/70 rounded-full" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  viewMode?: 'list' | 'compact';
}

withDefaults(defineProps<Props>(), {
  viewMode: 'list',
});
</script>
