<template>
  <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
    <div class="mb-6 rounded-full bg-muted/50 p-4">
      <slot name="icon">
        <Inbox class="h-10 w-10 text-muted-foreground" />
      </slot>
    </div>
    <h3 class="text-xl font-semibold tracking-tight mb-2">
      {{ title }}
    </h3>
    <p class="text-muted-foreground max-w-sm mb-6">
      {{ description }}
    </p>
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';

defineProps<{
  title: string;
  description: string;
}>();
</script>