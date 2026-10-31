<template>
  <details :open class="group" data-slot="field-history-disclosure" @toggle="onToggle">
    <summary
      :class="[
        'u-touch inline-flex cursor-pointer items-center gap-1.5',
        'text-xs font-bold uppercase tracking-wide text-foreground/80 hover:text-foreground transition-colors select-none',
      ]"
    >
      <ChevronDown class="size-3.5 transition-transform group-open:rotate-180" aria-hidden="true" />
      <span>{{ label }} ({{ count }})</span>
    </summary>

    <div class="mt-2 border border-border bg-background">
      <ul class="divide-y divide-border">
        <slot />
      </ul>
    </div>
  </details>
</template>

<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';

defineProps<{ label: string; count: number }>();
const open = defineModel<boolean>('open', { default: false });

function onToggle(event: Event): void {
  open.value = (event.target as HTMLDetailsElement).open;
}
</script>
