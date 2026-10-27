<template>
  <Sonner
    class="toaster group"
    v-bind="props"
    :offset="props.offset ?? { bottom: 'max(2rem, calc(max(var(--bottom-chrome-height,0px),var(--start-fm-clearance,0px)) + 1rem))' }"
    :mobile-offset="props.mobileOffset ?? { bottom: 'calc(var(--bottom-chrome-height,0px) + 1rem)' }"
    :style="{
      '--normal-bg': 'var(--popover)',
      '--normal-text': 'var(--popover-foreground)',
      '--normal-border': 'var(--border)',

    }"
  />
</template>

<script lang="ts" setup>
import { Toaster as Sonner, type ToasterProps } from 'vue-sonner';

const props = defineProps<ToasterProps>();
</script>
