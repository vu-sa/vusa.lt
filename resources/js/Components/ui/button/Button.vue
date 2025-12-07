<script setup lang="ts">
import type { PrimitiveProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import type { ButtonVariants } from "."
import { Primitive } from "reka-ui"
import { cn } from '@/Utils/Shadcn/utils'
import { buttonVariants } from "."

/**
 * Button component with consistent styling and optional animations
 * 
 * Animation variants:
 * - none: No animation (default)
 * - subtle: Gentle scale on hover (for interactive elements like search)
 * - bounce: Scale with active state (for call-to-action buttons)
 * 
 * Use animations sparingly - only for buttons that benefit from visual feedback
 */
interface Props extends PrimitiveProps {
  variant?: ButtonVariants["variant"]
  size?: ButtonVariants["size"]
  animation?: ButtonVariants['animation']
  class?: HTMLAttributes["class"]
}

const props = withDefaults(defineProps<Props>(), {
  as: "button",
})
</script>

<template>
  <Primitive
    data-slot="button"
    :as="as"
    :as-child="asChild"
    :class="cn(buttonVariants({ variant, size, animation }), props.class)"
  >
    <slot />
  </Primitive>
</template>
