<script lang="ts" setup>
import type { LabelProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'
import { Label } from '@/Components/ui/label'
import { useFormField } from './useFormField'

const props = defineProps<LabelProps & { class?: HTMLAttributes['class'] }>()

const { error, formItemId } = useFormField()
</script>

<template>
  <Label
    data-slot="form-label"
    :data-error="!!error"
    :class="cn(
      'data-[error=true]:text-red-600 dark:data-[error=true]:text-red-800',
      props.class,
    )"
    :for="formItemId"
  >
    <slot />
  </Label>
</template>
