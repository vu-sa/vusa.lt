<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'
import { AvatarRoot } from 'reka-ui'
import { avatarVariants, type AvatarSize } from '.'

const props = withDefaults(defineProps<{
  class?: HTMLAttributes['class']
  size?: AvatarSize
  interactive?: boolean
}>(), {
  size: 'default',
  interactive: false,
})
</script>

<template>
  <AvatarRoot
    data-slot="avatar"
    :class="cn(avatarVariants({ size, interactive }), props.class)"
  >
    <slot />
  </AvatarRoot>
</template>
