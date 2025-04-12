<script setup lang="ts">
import { cn } from '@/Utils/Shadcn/utils'
import { AvatarFallback, type AvatarFallbackProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<AvatarFallbackProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <AvatarFallback
    data-slot="avatar-fallback"
    v-bind="delegatedProps"
    :class="cn('bg-zinc-100 flex size-full items-center justify-center rounded-full dark:bg-zinc-800', props.class)"
  >
    <slot />
  </AvatarFallback>
</template>
