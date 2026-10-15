<template>
  <CommandItem
    :value
    :class="paletteRowClass"
    @select="emit('select')"
  >
    <EntityTypeMark v-if="entityType" :type="entityType" size="lg" icon-only />
    <span v-else class="flex size-10 shrink-0 items-center justify-center border border-border bg-background text-foreground">
      <component :is="icon" class="size-5" :stroke-width="1.75" />
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

import { paletteRowClass } from './paletteRowClass';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { CommandItem } from '@/Components/ui/command';

defineProps<{
  value: string;
  icon: Component;
  title: string;
  subtitle?: string;
  /** Shows the type's category mark instead of the plain icon tile. */
  entityType?: string;
}>();

const emit = defineEmits<{ select: [] }>();
</script>
