<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card-content"
    :class="cn('p-6 pt-0 flex-grow', props.class)"
  >
    <slot />
  </div>
</template>
