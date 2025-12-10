<template>
  <div class="absolute -top-1 -right-1 z-10">
    <!-- Pulsing indicator dot -->
    <span class="relative flex h-3 w-3">
      <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary/75 opacity-75" />
      <span class="relative inline-flex h-3 w-3 rounded-full bg-primary" />
    </span>
  </div>
</template>

<script setup lang="ts">
/**
 * SpotlightBadge - A pulsing indicator for new features
 * 
 * Place this component inside a relative-positioned container to show
 * a pulsing dot that draws attention to new UI elements.
 * 
 * @example
 * ```vue
 * <div class="relative">
 *   <Button>New Feature</Button>
 *   <SpotlightBadge v-if="spotlight.isVisible.value" />
 * </div>
 * ```
 */
</script>
