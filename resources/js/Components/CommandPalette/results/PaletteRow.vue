<template>
  <CommandItem
    :value
    class="u-touch group cursor-pointer gap-3 px-3 py-2 data-[highlighted]:bg-secondary"
    @select="emit('select')"
  >
    <span class="flex size-8 shrink-0 items-center justify-center border border-border bg-background text-muted-foreground">
      <component :is="icon" class="size-4" />
    </span>
    <span class="min-w-0 flex-1">
      <span class="block truncate text-sm font-medium">{{ title }}</span>
      <span v-if="subtitle" class="block truncate text-xs text-muted-foreground">{{ subtitle }}</span>
    </span>
    <slot name="trailing" />
  </CommandItem>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

import { CommandItem } from '@/Components/ui/command';

defineProps<{
  value: string;
  icon: Component;
  title: string;
  subtitle?: string;
}>();

const emit = defineEmits<{ select: [] }>();
</script>
