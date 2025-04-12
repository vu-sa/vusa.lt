<script lang="ts" setup>
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'
import { Primitive, type PrimitiveProps } from 'reka-ui'

const props = withDefaults(defineProps<PrimitiveProps & { class?: HTMLAttributes['class'] }>(), {
  as: 'a',
})
</script>

<template>
  <Primitive
    data-slot="breadcrumb-link"
    :as="as"
    :as-child="asChild"
    :class="cn('hover:text-zinc-950 transition-colors dark:hover:text-zinc-50', props.class)"
  >
    <slot />
  </Primitive>
</template>
