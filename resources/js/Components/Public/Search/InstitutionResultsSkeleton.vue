<template>
  <div class="rounded-xl border border-border/40 bg-card p-4 sm:p-5 animate-pulse">
    <div class="flex gap-4">
      <!-- Logo skeleton -->
      <div class="flex-shrink-0">
        <div class="size-14 sm:size-16 rounded-full bg-muted" />
      </div>

      <!-- Content skeleton -->
      <div class="flex-1 space-y-3">
        <!-- Title -->
        <div class="h-5 bg-muted rounded w-3/4" />
        
        <!-- Tenant badge -->
        <div class="h-5 bg-muted rounded w-20" />
        
        <!-- Types -->
        <div class="flex gap-2">
          <div class="h-4 bg-muted rounded w-24" />
          <div class="h-4 bg-muted rounded w-16" />
        </div>

        <!-- Contact info -->
        <div class="flex gap-4">
          <div class="h-4 bg-muted rounded w-32" />
          <div class="h-4 bg-muted rounded w-20" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  viewMode?: 'grid' | 'list'
}>()
</script>
