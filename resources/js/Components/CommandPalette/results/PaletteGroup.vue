<template>
  <CommandGroup class="px-2 pt-2">
    <!-- The ActionWindow's list heading, so both windows read as one family. -->
    <template #heading>
      <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ heading }}</span>
    </template>
    <slot />
  </CommandGroup>
</template>

<script setup lang="ts">
import { CommandGroup } from '@/Components/ui/command';

defineProps<{ heading: string }>();
</script>
