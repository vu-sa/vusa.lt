<script lang="ts" setup>
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'
import SmartLink from '@/Components/Public/SmartLink.vue'

const props = withDefaults(defineProps<{ 
  class?: HTMLAttributes['class']
  href?: string
  prefetch?: boolean
  cacheFor?: string | number
}>(), {
  prefetch: true,
})
</script>

<template>
  <SmartLink
    data-slot="breadcrumb-link"
    :href="href"
    :prefetch="prefetch"
    :cache-for="cacheFor"
    :class="cn('text-zinc-600 hover:text-zinc-950 transition-colors dark:text-zinc-400 dark:hover:text-zinc-50', props.class)"
  >
    <slot />
  </SmartLink>
</template>
