<template>
  <span
    class="inline-flex shrink-0 items-center justify-center"
    :title="$t(status.label)"
    role="img"
    :aria-label="$t(status.label)"
    data-slot="collection-status-icon"
  >
    <component :is="status.icon" :class="['size-4', statusRoleParts[status.role].text]" aria-hidden="true" />
  </span>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { statusRoleParts, type StatusPresentation } from '@/Constants/statuses';

defineProps<{ status: StatusPresentation }>();
</script>
