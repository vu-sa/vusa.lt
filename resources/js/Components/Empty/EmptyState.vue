<template>
  <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
    <!-- A supplied icon keeps the muted chip it was sized for; the mascot stands on its own. -->
    <div v-if="$slots.icon" class="mb-6 rounded-full bg-muted/50 p-4">
      <slot name="icon" />
    </div>
    <TurtleMascot v-else class="mb-6 h-14 text-muted-foreground opacity-70" />
    <h3 class="text-xl font-semibold tracking-tight mb-2">
      {{ title }}
    </h3>
    <p class="text-muted-foreground max-w-sm mb-6">
      {{ description }}
    </p>
    <slot />
  </div>
</template>

<script setup lang="ts">
import TurtleMascot from './TurtleMascot.vue';

defineProps<{
  title: string;
  description: string;
}>();
</script>
