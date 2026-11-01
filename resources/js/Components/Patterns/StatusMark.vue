<template>
  <span
    :class="['flex size-5 shrink-0 items-center justify-center', parts.surface, parts.text]"
    :data-status-role="status.role"
    :title="$t(status.label)"
    data-slot="status-mark"
  >
    <component :is="icon" class="size-3.5" aria-hidden="true" />
    <span class="sr-only">{{ $t(status.label) }}</span>
  </span>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, type Component } from 'vue';

import { statusRoleParts, type StatusPresentation } from '@/Constants/statuses';

/** A record's own icon carrying its status colour, where a badge would add one more tag to the header. */
const props = defineProps<{
  icon: Component;
  status: StatusPresentation;
}>();

const parts = computed(() => statusRoleParts[props.status.role]);
</script>
