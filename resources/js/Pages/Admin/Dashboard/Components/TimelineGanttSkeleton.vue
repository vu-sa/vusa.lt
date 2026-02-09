<template>
  <Card class="overflow-hidden">
    <CardContent class="p-4">
      <!-- Header skeleton -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4">
          <!-- Legend skeleton -->
          <div class="flex items-center gap-4">
            <Skeleton class="h-4 w-16" />
            <Skeleton class="h-4 w-20" />
            <Skeleton class="h-4 w-24" />
          </div>
          <!-- Filters skeleton -->
          <div class="flex items-center gap-2">
            <Skeleton class="h-6 w-20" />
            <Skeleton class="h-6 w-16" />
          </div>
        </div>
        <!-- Controls skeleton -->
        <div class="flex items-center gap-3">
          <Skeleton class="h-6 w-24" />
          <Skeleton class="h-6 w-32" />
          <Skeleton class="h-6 w-16" />
          <Skeleton class="h-8 w-20" />
        </div>
      </div>

      <!-- Timeline container skeleton -->
      <div class="flex w-full border rounded-md overflow-hidden" style="height: 400px;">
        <!-- Left labels skeleton -->
        <div class="w-56 bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
          <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
            <Skeleton class="h-4 w-full" />
          </div>
          <div v-for="i in 8" :key="i" class="p-3 border-b border-zinc-100 dark:border-zinc-800" :class="i % 2 === 0 ? 'bg-zinc-50/40 dark:bg-zinc-800/30' : ''">
            <div class="flex items-center justify-between">
              <Skeleton class="h-4 w-32" />
              <Skeleton class="h-3 w-12" />
            </div>
          </div>
        </div>

        <!-- Right timeline skeleton -->
        <div class="flex-1 bg-zinc-50 dark:bg-zinc-900">
          <!-- Timeline axis skeleton -->
          <div class="h-6 border-b bg-white px-4 flex items-center gap-8">
            <Skeleton v-for="i in 6" :key="i" class="h-3 w-8" />
          </div>

          <!-- Timeline rows skeleton -->
          <div class="p-4 space-y-4">
            <div v-for="row in 8" :key="row" class="flex items-center gap-4">
              <!-- Meeting circles skeleton -->
              <Skeleton v-for="i in Math.floor(Math.random() * 4) + 1" :key="i"
                class="w-2 h-2 rounded-full"
                :style="{ marginLeft: Math.random() * 200 + 'px' }" />
              <!-- Gap bars skeleton -->
              <Skeleton class="h-1 rounded"
                :style="{
                  width: Math.random() * 100 + 50 + 'px',
                  marginLeft: Math.random() * 150 + 'px'
                }" />
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
</script>
