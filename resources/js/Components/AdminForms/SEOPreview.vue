<template>
  <Card class="border-dashed">
    <CardHeader class="pb-3">
      <CardTitle class="flex items-center gap-2 text-sm font-medium">
        <IFluentSearch24Regular class="h-4 w-4" />
        {{ $t('Paieškos rezultatų peržiūra') }}
      </CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <!-- Google-style preview -->
      <div class="rounded-lg border bg-white p-4 font-sans dark:bg-zinc-900">
        <div class="space-y-1">
          <!-- Breadcrumb/URL -->
          <div class="flex items-center gap-1 text-xs text-zinc-600 dark:text-zinc-400">
            <IFluentGlobe16Regular class="h-3 w-3" />
            <span class="truncate">{{ displayUrl }}</span>
          </div>
          <!-- Title -->
          <h3 class="line-clamp-1 cursor-pointer text-lg font-medium text-blue-700 hover:underline dark:text-blue-400">
            {{ title || $t('Puslapio pavadinimas') }}
          </h3>
          <!-- Description -->
          <p class="line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">
            {{ description || $t('Meta aprašymas bus rodomas čia. Rekomenduojama 120-160 simbolių.') }}
          </p>
        </div>
      </div>

      <!-- Character count feedback -->
      <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs">
        <div class="flex items-center gap-2">
          <div class="h-2 w-2 rounded-full" :class="titleDotClass" />
          <span class="text-muted-foreground">{{ $t('Pavadinimas') }}:</span>
          <span :class="titleLengthClass">{{ titleLength }}/60</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="h-2 w-2 rounded-full" :class="descriptionDotClass" />
          <span class="text-muted-foreground">{{ $t('Aprašymas') }}:</span>
          <span :class="descriptionLengthClass">{{ descriptionLength }}/160</span>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap items-center gap-4 border-t pt-3 text-[10px] text-muted-foreground">
        <div class="flex items-center gap-1.5">
          <div class="h-2 w-2 rounded-full bg-green-500" />
          <span>{{ $t('Optimalu') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="h-2 w-2 rounded-full bg-amber-500" />
          <span>{{ $t('Per trumpa') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="h-2 w-2 rounded-full bg-red-500" />
          <span>{{ $t('Per ilga') }}</span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const props = defineProps<{
  title?: string;
  description?: string;
  url?: string;
  baseUrl?: string;
}>();

const displayUrl = computed(() => {
  const base = props.baseUrl || 'vusa.lt';
  const path = props.url || 'puslapio-nuoroda';
  return `${base} › ${path}`;
});

const titleLength = computed(() => props.title?.length || 0);
const descriptionLength = computed(() => props.description?.length || 0);

// Title: optimal 30-60, short <30, long >60
const titleLengthClass = computed(() => {
  const len = titleLength.value;
  if (len === 0) return 'text-zinc-400';
  if (len > 60) return 'text-red-600 dark:text-red-400 font-medium';
  if (len < 30) return 'text-amber-600 dark:text-amber-400';
  return 'text-green-600 dark:text-green-400';
});

const titleDotClass = computed(() => {
  const len = titleLength.value;
  if (len === 0) return 'bg-zinc-300 dark:bg-zinc-600';
  if (len > 60) return 'bg-red-500';
  if (len < 30) return 'bg-amber-500';
  return 'bg-green-500';
});

// Description: optimal 120-160, short <120, long >160
const descriptionLengthClass = computed(() => {
  const len = descriptionLength.value;
  if (len === 0) return 'text-zinc-400';
  if (len > 160) return 'text-red-600 dark:text-red-400 font-medium';
  if (len < 120) return 'text-amber-600 dark:text-amber-400';
  return 'text-green-600 dark:text-green-400';
});

const descriptionDotClass = computed(() => {
  const len = descriptionLength.value;
  if (len === 0) return 'bg-zinc-300 dark:bg-zinc-600';
  if (len > 160) return 'bg-red-500';
  if (len < 120) return 'bg-amber-500';
  return 'bg-green-500';
});
</script>
