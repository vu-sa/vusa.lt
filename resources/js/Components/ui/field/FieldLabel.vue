<template>
  <Label
    data-slot="field-label"
    :class="cn(
      'group/field-label peer/field-label flex w-fit gap-2 leading-snug group-data-[disabled=true]/field:opacity-50',
      'has-[>[data-slot=field]]:w-full has-[>[data-slot=field]]:flex-col has-[>[data-slot=field]]:rounded-md has-[>[data-slot=field]]:border [&_>[data-slot=field]]:p-4',
      'has-[[data-state=checked]]:bg-zinc-900/5 has-[[data-state=checked]]:border-zinc-900 dark:has-[[data-state=checked]]:bg-zinc-900/10 dark:has-[[data-state=checked]]:bg-zinc-50/5 dark:has-[[data-state=checked]]:border-zinc-50 dark:dark:has-[[data-state=checked]]:bg-zinc-50/10',
      props.class,
    )"
  >
    <slot />
  </Label>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';
import { Label } from '@/Components/ui/label';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>
