<template>
  <AlertDialogDescription
    v-bind="delegatedProps"
    :class="cn('text-sm text-zinc-500 dark:text-zinc-400', props.class)"
  >
    <slot />
  </AlertDialogDescription>
</template>

<script setup lang="ts">
import type { AlertDialogDescriptionProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import {
  AlertDialogDescription,
} from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<AlertDialogDescriptionProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>
