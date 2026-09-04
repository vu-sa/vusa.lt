<template>
  <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-border bg-card transition-all duration-300 hover:border-brand">
    <div v-if="element.options?.title" data-slot="card-header" class="px-5 pt-5 pb-3">
      <h3 data-slot="card-title" class="text-lg font-bold leading-tight tracking-tight mb-1.5 text-foreground">
        {{ element.options.title }}
      </h3>
    </div>
    <div class="rc-prose tracking-normal px-5 pb-5" :class="{ 'pt-5': !element.options?.title }">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  element: models.ContentPart;
}>();
</script>
