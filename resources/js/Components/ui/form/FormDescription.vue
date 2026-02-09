<template>
  <p
    :id="formDescriptionId"
    data-slot="form-description"
    :class="cn('text-zinc-500 text-sm dark:text-zinc-400', props.class)"
  >
    <slot />
  </p>
</template>

<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';

import { useFormField } from './useFormField';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const { formDescriptionId } = useFormField();
</script>
