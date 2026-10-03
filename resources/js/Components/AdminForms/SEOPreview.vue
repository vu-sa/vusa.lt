<template>
  <div class="border border-dashed border-border bg-card p-4 space-y-4">
    <div class="flex items-center gap-2 text-sm font-medium text-foreground">
      <Search class="h-4 w-4" />
      {{ $t('Paieškos rezultatų peržiūra') }}
    </div>

    <!-- Google-style preview -->
    <div class="border border-border bg-secondary p-4 font-sans">
      <div class="space-y-1">
        <!-- Breadcrumb/URL -->
        <div class="flex items-center gap-1 text-xs text-muted-foreground">
          <Globe class="h-3 w-3" />
          <span class="truncate">{{ displayUrl }}</span>
        </div>
        <!-- Title -->
        <h3 class="line-clamp-1 cursor-pointer text-lg font-medium text-primary hover:underline">
          {{ title || $t('Puslapio pavadinimas') }}
        </h3>
        <!-- Description -->
        <p class="line-clamp-2 text-sm text-muted-foreground">
          {{ description || $t('Meta aprašymas bus rodomas čia. Rekomenduojama 120-160 simbolių.') }}
        </p>
      </div>
    </div>

    <!-- Character count feedback -->
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs">
      <div class="flex items-center gap-2">
        <div class="size-2" :class="titleDotClass" />
        <span class="text-muted-foreground">{{ $t('Pavadinimas') }}:</span>
        <span :class="titleLengthClass">{{ titleLength }}/60</span>
      </div>
      <div class="flex items-center gap-2">
        <div class="size-2" :class="descriptionDotClass" />
        <span class="text-muted-foreground">{{ $t('Aprašymas') }}:</span>
        <span :class="descriptionLengthClass">{{ descriptionLength }}/160</span>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-4 border-t border-border pt-3 text-[10px] text-muted-foreground">
      <div class="flex items-center gap-1.5">
        <div class="size-2 bg-[var(--status-success)]" />
        <span>{{ $t('Optimalu') }}</span>
      </div>
      <div class="flex items-center gap-1.5">
        <div class="size-2 bg-[var(--status-attention)]" />
        <span>{{ $t('Per trumpa') }}</span>
      </div>
      <div class="flex items-center gap-1.5">
        <div class="size-2 bg-[var(--status-danger)]" />
        <span>{{ $t('Per ilga') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Globe, Search } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

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
  if (len === 0) return 'text-muted-foreground';
  if (len > 60) return 'text-destructive font-medium';
  if (len < 30) return 'text-[var(--status-attention)]';
  return 'text-[var(--status-success)]';
});

const titleDotClass = computed(() => {
  const len = titleLength.value;
  if (len === 0) return 'bg-muted';
  if (len > 60) return 'bg-[var(--status-danger)]';
  if (len < 30) return 'bg-[var(--status-attention)]';
  return 'bg-[var(--status-success)]';
});

// Description: optimal 120-160, short <120, long >160
const descriptionLengthClass = computed(() => {
  const len = descriptionLength.value;
  if (len === 0) return 'text-muted-foreground';
  if (len > 160) return 'text-destructive font-medium';
  if (len < 120) return 'text-[var(--status-attention)]';
  return 'text-[var(--status-success)]';
});

const descriptionDotClass = computed(() => {
  const len = descriptionLength.value;
  if (len === 0) return 'bg-muted';
  if (len > 160) return 'bg-[var(--status-danger)]';
  if (len < 120) return 'bg-[var(--status-attention)]';
  return 'bg-[var(--status-success)]';
});
</script>
