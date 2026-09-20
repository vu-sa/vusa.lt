<template>
  <ul
    class="grid grid-cols-2 gap-x-6 gap-y-4 border-t border-border pt-4 lg:grid-cols-4"
    data-slot="overview-numbers"
  >
    <li v-for="number in numbers" :key="number.key">
      <Link
        :href="number.href"
        :class="[
          'group flex min-h-11 flex-col gap-1 underline-offset-4 hover:underline focus-visible:underline',
          'pointer-coarse:min-h-11',
        ]"
        :data-number="number.key"
      >
        <span :class="['text-3xl font-semibold tabular-nums leading-none', toneClass(number)]">
          {{ number.value }}
        </span>
        <span class="text-sm text-muted-foreground group-hover:text-foreground">
          {{ number.label }}
        </span>
      </Link>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import type { StatusRole } from '@/Constants/statuses';

export interface OverviewNumberItem {
  key: string;
  label: string;
  value: number | string;
  /** Every number is a link to the filtered collection it counts (O17). */
  href: string;
  /** Only lit while the number is non-zero: a healthy zero is not painted. */
  tone?: StatusRole;
}

defineProps<{
  numbers: OverviewNumberItem[];
}>();

const toneClasses: Record<StatusRole, string> = {
  neutral: 'text-status-neutral',
  info: 'text-status-info',
  progress: 'text-status-progress',
  attention: 'text-status-attention',
  success: 'text-status-success',
  danger: 'text-status-danger',
};

function toneClass(number: OverviewNumberItem): string {
  if (!number.tone || Number(number.value) === 0) {
    return 'text-foreground';
  }

  return toneClasses[number.tone];
}
</script>
