<template>
  <span
    :class="['inline-flex items-center gap-1.5 text-sm', option ? 'text-foreground' : 'font-medium text-status-attention']"
    data-slot="agenda-item-type"
  >
    <component :is="option?.icon ?? agendaItemStatuses.unset.icon" class="size-4 shrink-0" aria-hidden="true" />
    {{ option?.label ?? $t(agendaItemStatuses.unset.label) }}
  </span>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { agendaItemTypeOptions } from './agendaItemTypes';

import { agendaItemStatuses } from '@/Constants/statuses';

const props = defineProps<{ type?: string | null }>();

const option = computed(() => agendaItemTypeOptions().find(candidate => candidate.value === props.type));
</script>
