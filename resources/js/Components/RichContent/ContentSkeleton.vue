<template>
  <div v-if="type === 'shadcn-accordion'">
    <div class="w-full py-16 px-4 bg-secondary/40">
      <div class="space-y-4">
        <div v-for="i in 3" :key="i" class="border border-border rounded-lg p-4 bg-card">
          <Skeleton class="h-5 w-3/4" />
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="type === 'image-grid'">
    <div class="w-full py-4">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <Skeleton v-for="i in 6" :key="i" class="aspect-square rounded-md" />
      </div>
    </div>
  </div>
  <div v-else-if="type === 'hero'">
    <div class="w-full min-h-[45rem] bg-secondary/40 animate-pulse flex items-end justify-center pb-16">
      <div class="flex flex-col items-center gap-4 max-w-2xl px-8">
        <Skeleton class="h-12 w-96 max-w-full" />
        <Skeleton class="h-6 w-64 max-w-full" />
        <Skeleton class="h-12 w-40 rounded-full mt-4" />
      </div>
    </div>
  </div>
  <div v-else-if="type === 'news'">
    <div class="w-full py-4">
      <div class="grid gap-4 sm:gap-6 grid-cols-1 lg:grid-cols-[2fr_1fr] px-4 md:px-8">
        <div class="space-y-4">
          <Skeleton class="aspect-video w-full rounded-md" />
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-8 w-3/4" />
          <Skeleton class="h-20 w-full" />
        </div>
        <div class="space-y-3">
          <Skeleton class="h-6 w-24" />
          <div v-for="i in 4" :key="i" class="flex items-center gap-3 py-2">
            <Skeleton class="w-16 h-12 rounded flex-shrink-0" />
            <div class="flex-1 space-y-2">
              <Skeleton class="h-3 w-full" />
              <Skeleton class="h-2 w-20" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="type === 'calendar'">
    <div class="w-full py-8 px-4 md:px-8">
      <Skeleton class="h-8 w-48 mb-6" />
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 6" :key="i" class="space-y-3 p-4 border border-border rounded-lg">
          <Skeleton class="h-4 w-24" />
          <Skeleton class="h-6 w-full" />
          <Skeleton class="h-3 w-3/4" />
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="type === 'institution-list'">
    <div class="w-full py-8 px-4 md:px-8">
      <Skeleton class="h-8 w-48 mb-6" />
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="space-y-3 p-4 border border-border bg-card">
          <Skeleton class="aspect-[16/10] w-full" />
          <Skeleton class="h-6 w-3/4" />
          <Skeleton class="h-4 w-full" />
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="type === 'number-stat-section'">
    <div class="w-full py-12 px-4 md:px-8">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
        <div v-for="i in 4" :key="i" class="flex flex-col items-center gap-2">
          <Skeleton class="h-16 w-24" />
          <Skeleton class="h-4 w-32" />
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="type === 'text-box'">
    <div class="w-full rounded-lg border border-border p-6">
      <Skeleton class="h-6 w-48 mb-4" />
      <Skeleton class="h-28 w-full mb-3" />
      <Skeleton class="h-10 w-28 rounded-md" />
    </div>
  </div>
  <div v-else-if="type === 'carousel-slide-deck'">
    <div class="w-full py-12 px-4">
      <div class="grid lg:grid-cols-2 gap-8 md:gap-12 items-center bg-card rounded-2xl p-8 md:p-12 border border-border">
        <div class="space-y-4 md:space-y-6">
          <Skeleton class="h-6 w-32" />
          <Skeleton class="h-8 w-3/4" />
          <Skeleton class="h-20 w-full" />
        </div>
        <Skeleton class="h-64 md:h-80" />
      </div>
    </div>
  </div>
  <div v-else-if="type === 'hero-carousel'">
    <div class="w-full px-4 pt-4 pb-4 sm:px-6 sm:pt-6 sm:pb-5 lg:px-8">
      <div class="min-h-[22rem] rounded-2xl md:rounded-3xl bg-secondary/40 animate-pulse relative overflow-hidden">
        <div class="absolute inset-0 flex items-end p-8">
          <div class="space-y-3 max-w-xl">
            <Skeleton class="h-4 w-24" />
            <Skeleton class="h-10 w-3/4" />
            <Skeleton class="h-5 w-1/2" />
            <Skeleton class="h-11 w-36 rounded-full" />
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="type === 'card-stack'">
    <div class="w-full py-16 bg-secondary/40 px-4">
      <div class="max-w-lg mx-auto">
        <Skeleton class="h-80 w-full rounded-xl border border-border" />
        <div class="flex justify-center mt-8 space-x-2">
          <Skeleton v-for="i in 3" :key="i" class="w-3 h-3 rounded-full" />
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="type === 'photo-gallery'">
    <div class="w-full py-12 px-4">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-4">
        <Skeleton v-for="i in 8" :key="i" class="h-52 rounded-lg" />
      </div>
    </div>
  </div>
  <div v-else-if="type === 'link-list'">
    <div class="w-full py-16 px-4">
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Skeleton v-for="i in 3" :key="i" class="h-64 rounded-2xl" />
      </div>
    </div>
  </div>
  <div v-else-if="type === 'event-list'">
    <div class="w-full py-16 px-4">
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <Skeleton v-for="i in 3" :key="i" class="h-64 rounded-2xl" />
      </div>
    </div>
  </div>
  <div v-else-if="type === 'section'">
    <div class="w-full py-16 px-4 flex flex-col items-center gap-3">
      <Skeleton class="h-7 w-64 max-w-full" />
      <Skeleton class="h-4 w-40" />
    </div>
  </div>
  <div v-else-if="type === 'process-steps'">
    <div class="grid w-full gap-8 py-16 sm:grid-cols-3">
      <div v-for="i in 3" :key="i" class="space-y-3">
        <Skeleton class="h-8 w-12" />
        <Skeleton class="h-5 w-32" />
        <Skeleton class="h-12 w-full" />
      </div>
    </div>
  </div>
  <div v-else-if="type === 'person-quote'">
    <div class="w-full py-12 px-4 flex flex-col items-center gap-4">
      <Skeleton class="h-6 w-2/3" />
      <Skeleton class="h-6 w-1/2" />
      <Skeleton class="h-10 w-10 rounded-full" />
    </div>
  </div>
  <div v-else-if="type === 'timetable'">
    <div class="w-full overflow-hidden border border-border bg-secondary/40">
      <div class="flex items-center gap-2 border-b border-border px-5 py-3">
        <Skeleton class="h-4 w-24" />
      </div>
      <div v-for="i in 3" :key="i" class="flex items-center gap-4 px-5 py-3">
        <Skeleton class="h-4 w-16" />
        <Skeleton class="h-4 flex-1" />
      </div>
    </div>
  </div>
  <div v-else>
    <div class="w-full py-4 space-y-4">
      <Skeleton class="h-6 w-3/4" />
      <Skeleton class="h-4 w-full" />
      <Skeleton class="h-4 w-5/6" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Skeleton } from '@/Components/ui/skeleton';

defineProps<{ type: string }>();
</script>
