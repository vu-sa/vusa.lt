<template>
  <MediaFrame v-bind="$attrs">
    <template v-if="$slots.fallback" #fallback>
      <slot name="fallback" />
    </template>
    <template v-if="$slots.default" #default>
      <slot />
    </template>
  </MediaFrame>
</template>

<script setup lang="ts">
import MediaFrame from '@/Components/Brand/MediaFrame.vue';

defineOptions({ inheritAttrs: false });
</script>
