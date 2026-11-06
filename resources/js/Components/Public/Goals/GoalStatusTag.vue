<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 border px-2 py-0.5 text-xs font-bold uppercase tracking-wide',
      parts.text, parts.surface, parts.border,
    ]"
  >
    <component :is="icons[status]" class="size-3.5" aria-hidden="true" />
    {{ label }}
  </span>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';

import { goalStatuses, statusRoleParts } from '@/Constants/statuses';
import { GoalStatus } from '@/Types/enums';

import IFluentCalendarClock from '~icons/fluent/calendar-clock-24-regular';
import IFluentArrowSyncCircle from '~icons/fluent/arrow-sync-circle-24-regular';
import IFluentCheckmarkCircle from '~icons/fluent/checkmark-circle-24-regular';
import IFluentDismissCircle from '~icons/fluent/dismiss-circle-24-regular';
import IFluentProhibited from '~icons/fluent/prohibited-24-regular';

const props = defineProps<{
  status: GoalStatus;
  /** Localized on the server (GoalStatus::label()). */
  label: string;
}>();

const icons: Record<GoalStatus, Component> = {
  [GoalStatus.Planned]: IFluentCalendarClock,
  [GoalStatus.InProgress]: IFluentArrowSyncCircle,
  [GoalStatus.Achieved]: IFluentCheckmarkCircle,
  [GoalStatus.NotAchieved]: IFluentDismissCircle,
  [GoalStatus.Dropped]: IFluentProhibited,
};
const presentation = computed(() => goalStatuses[props.status]);
const parts = computed(() => statusRoleParts[presentation.value.role]);
</script>
