<script lang="ts" setup>
import type { DrawerTitleProps } from 'vaul-vue'
import { cn } from '@/Utils/Shadcn/utils'
import { DrawerTitle } from 'vaul-vue'
import { computed, type HtmlHTMLAttributes } from 'vue'

const props = defineProps<DrawerTitleProps & { class?: HtmlHTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <DrawerTitle
    data-slot="drawer-title"
    v-bind="delegatedProps"
    :class="cn('text-zinc-950 font-semibold dark:text-zinc-50', props.class)"
  >
    <slot />
  </DrawerTitle>
</template>
