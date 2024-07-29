<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/shadcn'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div :class="cn('text-sm', props.class)">
    <slot />
  </div>
</template>
