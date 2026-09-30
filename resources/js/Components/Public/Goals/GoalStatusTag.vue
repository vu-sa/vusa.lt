<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 border px-2 py-0.5 text-xs font-bold uppercase tracking-wide',
      parts.text, parts.surface, parts.border,
    ]"
  >
    <component :is="presentation.icon" class="size-3.5" aria-hidden="true" />
    {{ label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { goalStatuses, statusRoleParts } from '@/Constants/statuses';
import type { GoalStatus } from '@/Types/enums';

const props = defineProps<{
  status: GoalStatus;
  /** Localized on the server (GoalStatus::label()). */
  label: string;
}>();

const presentation = computed(() => goalStatuses[props.status]);
const parts = computed(() => statusRoleParts[presentation.value.role]);
</script>
