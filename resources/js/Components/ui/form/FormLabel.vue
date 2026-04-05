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

<script lang="ts" setup>
import type { LabelProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

import { useFormField } from './useFormField';

import { cn } from '@/Utils/Shadcn/utils';
import { Label } from '@/Components/ui/label';

const props = defineProps<LabelProps & { class?: HTMLAttributes['class'] }>();

const { error, formItemId } = useFormField();
</script>
