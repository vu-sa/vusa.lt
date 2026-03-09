<template>
  <div class="animate-pulse space-y-6 p-6">
    <!-- Header skeleton -->
    <div class="flex items-center justify-between">
      <div class="space-y-2">
        <Skeleton class="h-8 w-48" />
        <Skeleton class="h-4 w-64" />
      </div>
      <Skeleton class="h-10 w-32" />
    </div>

    <!-- Content skeleton -->
    <div class="space-y-4">
      <!-- Card/Table header -->
      <div class="flex items-center gap-4">
        <Skeleton class="h-10 w-64" />
        <Skeleton class="h-10 w-32" />
      </div>
      
      <!-- Table rows or cards -->
      <div class="rounded-lg border">
        <div class="border-b p-4">
          <div class="flex items-center gap-4">
            <Skeleton class="h-4 w-4" />
            <Skeleton class="h-4 flex-1 max-w-[200px]" />
            <Skeleton class="h-4 flex-1 max-w-[150px]" />
            <Skeleton class="h-4 flex-1 max-w-[100px]" />
            <Skeleton class="h-4 w-20" />
          </div>
        </div>
        <div v-for="i in 5" :key="i" class="border-b p-4 last:border-b-0">
          <div class="flex items-center gap-4">
            <Skeleton class="h-4 w-4" />
            <Skeleton class="h-4 flex-1 max-w-[200px]" />
            <Skeleton class="h-4 flex-1 max-w-[150px]" />
            <Skeleton class="h-4 flex-1 max-w-[100px]" />
            <Skeleton class="h-8 w-20" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Skeleton } from '@/Components/ui/skeleton';
</script>
