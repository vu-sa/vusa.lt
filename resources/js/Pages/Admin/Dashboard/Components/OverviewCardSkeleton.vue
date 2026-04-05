<template>
  <Card class="flex flex-col relative overflow-hidden">
    <!-- Status indicator skeleton -->
    <div class="absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45 bg-zinc-200 dark:bg-zinc-700" />

    <CardHeader class="pb-4 relative z-10">
      <CardTitle class="flex items-center gap-2">
        <Skeleton class="h-5 w-5 rounded-full" />
        <Skeleton class="h-6 w-32" />
        <Skeleton class="h-6 w-12 ml-auto" />
      </CardTitle>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 space-y-4">
      <!-- Primary metric skeleton -->
      <div class="text-center">
        <Skeleton class="h-8 w-16 mx-auto mb-2" />
        <Skeleton class="h-4 w-24 mx-auto" />
      </div>

      <!-- Simplified institution list skeleton -->
      <div class="space-y-2">
        <div v-for="i in 5" :key="i" class="flex items-center justify-between p-3 rounded-lg border">
          <div class="flex-1 flex items-center gap-2">
            <Skeleton class="h-2 w-2 rounded-full" />
            <Skeleton class="h-4 w-32" />
            <Skeleton class="h-4 w-16 rounded-full" />
          </div>
          <Skeleton class="h-8 w-24 rounded" />
        </div>
      </div>

      <!-- Activity overview skeleton -->
      <div class="space-y-3">
        <Skeleton class="h-2 w-full rounded-full" />
        <div class="text-center p-3 border rounded-lg">
          <Skeleton class="h-4 w-24 mx-auto mb-2" />
          <Skeleton class="h-8 w-20 mx-auto" />
        </div>
      </div>
    </CardContent>

    <CardFooter class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-800/40 p-3 relative z-10">
      <div class="flex gap-2 w-full">
        <Skeleton class="h-8 flex-1 rounded" />
        <Skeleton class="h-8 w-28 rounded" />
      </div>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/Components/ui/card';
import { Skeleton } from '@/Components/ui/skeleton';
</script>
