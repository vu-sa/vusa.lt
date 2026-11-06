<template>
  <time
    :datetime="date.toISOString()"
    class="flex size-16 shrink-0 flex-col items-center justify-center border border-border bg-card text-center"
  >
    <!-- Month first, as Lithuanian dates are read ("RGS 24"). -->
    <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ month }}</span>
    <span class="mt-1 text-2xl font-semibold leading-none text-foreground">{{ day }}</span>
  </time>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { formatDateWith, formatMonthAbbr, parseDate } from '@/Utils/dateTime';

const props = defineProps<{
  value: string;
}>();

const date = computed(() => parseDate(props.value) ?? new Date(Number.NaN));
const day = computed(() => formatDateWith(date.value, { day: 'numeric' }));
const month = computed(() => formatMonthAbbr(date.value));
</script>
