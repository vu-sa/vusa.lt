<script lang="ts" setup>
import type { LabelProps } from 'radix-vue'
import type { HTMLAttributes } from 'vue'
import { Label } from '@/Components/ShadcnVue/ui/label'
import { cn } from '@/Utils/shadcn'
import { useFormField } from './useFormField'

const props = defineProps<LabelProps & { class?: HTMLAttributes['class'] }>()

const { error, formItemId } = useFormField()
</script>

<template>
  <Label
    :class="cn(
      error && 'text-red-500 dark:text-red-900',
      props.class,
    )"
    :for="formItemId"
  >
    <slot />
  </Label>
</template>
