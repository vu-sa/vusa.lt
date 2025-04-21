<script lang="ts" setup>
import type { DrawerDescriptionProps } from 'vaul-vue'
import { cn } from '@/Utils/Shadcn/utils'
import { DrawerDescription } from 'vaul-vue'
import { computed, type HtmlHTMLAttributes } from 'vue'

const props = defineProps<DrawerDescriptionProps & { class?: HtmlHTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <DrawerDescription
    data-slot="drawer-description"
    v-bind="delegatedProps"
    :class="cn('text-zinc-500 text-sm dark:text-zinc-400', props.class)"
  >
    <slot />
  </DrawerDescription>
</template>
