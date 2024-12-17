<script lang="ts" setup>
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/shadcn'
import { useFormField } from './useFormField'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { formDescriptionId } = useFormField()
</script>

<template>
  <p
    :id="formDescriptionId"
    :class="cn('text-sm text-zinc-500 dark:text-zinc-400', props.class)"
  >
    <slot />
  </p>
</template>
