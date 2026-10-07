<template>
  <span class="contents" data-slot="path-breadcrumb">
    <button
      type="button"
      :class="[
        'inline-flex min-h-11 items-center gap-1.5 px-1.5 py-0.5 font-medium transition-colors hover:text-brand',
        crumbs.length === 0 ? 'text-brand font-semibold' : 'text-muted-foreground',
      ]"
      @click="emit('navigate', rootPath)"
    >
      <Home class="size-3.5 shrink-0" aria-hidden="true" />
      <span>{{ rootLabel }}</span>
    </button>
    <template v-for="(crumb, index) in crumbs" :key="crumb.path">
      <ChevronRight class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
      <button
        type="button"
        :class="[
          'inline-flex min-h-11 max-w-[160px] items-center truncate px-1.5 py-0.5 font-medium transition-colors hover:text-brand',
          index === crumbs.length - 1 ? 'text-foreground font-semibold' : 'text-muted-foreground',
        ]"
        :aria-current="index === crumbs.length - 1 ? 'location' : undefined"
        @click="emit('navigate', crumb.path)"
      >
        {{ crumb.name }}
      </button>
    </template>
  </span>
</template>

<script setup lang="ts">
import { ChevronRight, Home } from 'lucide-vue-next';

/** The folder path as clickable steps; the caller owns what a path means (local uploads or SharePoint). */
defineProps<{
  crumbs: { name: string; path: string }[];
  rootLabel: string;
  rootPath: string;
}>();

const emit = defineEmits<{
  navigate: [path: string];
}>();
</script>
