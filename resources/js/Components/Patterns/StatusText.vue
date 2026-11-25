<template>
  <!-- A status inside a row's sub-line: StatusBadge's icon and word in its role colour, without the box. -->
  <span
    :class="cn('inline-flex items-center gap-1', statusRoleParts[status.role].text, props.class)"
    data-slot="status-text"
    :data-status-role="status.role"
  >
    <component :is="status.icon" class="size-3.5 shrink-0" aria-hidden="true" />
    <span>{{ $t(status.label) }}</span>
  </span>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import type { HTMLAttributes } from 'vue';

import { statusRoleParts } from '@/Constants/statuses';
import type { StatusPresentation } from '@/Constants/statusIcons';
import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  status: StatusPresentation;
  class?: HTMLAttributes['class'];
}>();
</script>
