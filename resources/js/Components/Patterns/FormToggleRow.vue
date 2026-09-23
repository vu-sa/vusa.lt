<template>
  <label
    :class="cn(
      'flex cursor-pointer items-start justify-between gap-3 border-b border-border px-4 py-3 transition-colors last:border-b-0 hover:bg-secondary/40',
      props.class,
    )"
    data-slot="form-toggle-row"
  >
    <span class="flex flex-col gap-0.5">
      <span class="text-sm font-bold text-foreground">{{ label }}</span>
      <span v-if="hint" class="text-xs leading-relaxed text-muted-foreground">{{ hint }}</span>
    </span>
    <Switch
      v-model="model"
      class="mt-0.5 shrink-0 data-[state=checked]:bg-brand-fill dark:data-[state=checked]:bg-brand-fill"
      :disabled
    />
  </label>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { Switch } from '@/Components/ui/switch';
import { cn } from '@/Utils/Shadcn/utils';

/** One setting as a whole-row target: bold label, a hint, and a switch (hairline between rows). */
const props = defineProps<{
  label: string;
  hint?: string;
  disabled?: boolean;
  class?: HTMLAttributes['class'];
}>();

const model = defineModel<boolean>({ default: false });
</script>
