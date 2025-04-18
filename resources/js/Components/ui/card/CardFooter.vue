<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card-footer"
    :class="cn('flex items-center p-6 mt-auto', props.class)"
  >
    <slot />
  </div>
</template>
