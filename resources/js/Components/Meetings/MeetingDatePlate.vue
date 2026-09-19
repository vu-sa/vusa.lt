<template>
  <time
    :datetime="date.toISOString()"
    class="flex size-16 shrink-0 flex-col items-center justify-center border border-border bg-card text-center"
  >
    <span class="text-2xl font-semibold leading-none text-foreground">{{ day }}</span>
    <span class="mt-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ month }}</span>
  </time>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { formatMonthShort, formatStaticTime } from '@/Utils/IntlTime';

const props = defineProps<{
  value: string;
}>();

const date = computed(() => new Date(props.value));
const day = computed(() => formatStaticTime(date.value, { day: 'numeric' }));
const month = computed(() => formatMonthShort(date.value));
</script>
