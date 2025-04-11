<script lang="ts" setup>
import { cn } from '@/Utils/Shadcn/utils'
import { ErrorMessage } from 'vee-validate'
import { type HTMLAttributes, toValue } from 'vue'
import { useFormField } from './useFormField'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { name, formMessageId } = useFormField()
</script>

<template>
  <ErrorMessage
    :id="formMessageId"
    data-slot="form-message"
    as="p"
    :name="toValue(name)"
    :class="cn('text-red-600 text-sm dark:text-red-800', props.class)"
  />
</template>
