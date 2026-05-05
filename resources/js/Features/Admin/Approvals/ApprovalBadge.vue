<template>
  <Badge :variant="badgeVariant" class="gap-1">
    <component :is="iconComponent" class="size-3" />
    {{ label }}
  </Badge>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Badge } from '@/Components/ui/badge';
import IFluentCheckmark24Filled from '~icons/fluent/checkmark-24-filled';
import IFluentDismiss24Filled from '~icons/fluent/dismiss-24-filled';
import IFluentArrowUndo24Filled from '~icons/fluent/arrow-undo-24-filled';

type ApprovalDecision = 'approved' | 'rejected' | 'cancelled';

const props = defineProps<{
  decision: ApprovalDecision;
}>();

const badgeVariant = computed(() => {
  switch (props.decision) {
    case 'approved':
      return 'success';
    case 'rejected':
      return 'destructive';
    case 'cancelled':
      return 'warning';
    default:
      return 'secondary';
  }
});

const label = computed(() => {
  switch (props.decision) {
    case 'approved':
      return $t('Patvirtinta');
    case 'rejected':
      return $t('Atmesta');
    case 'cancelled':
      return $t('Atšaukta');
    default:
      return props.decision;
  }
});

const iconComponent = computed(() => {
  switch (props.decision) {
    case 'approved':
      return IFluentCheckmark24Filled;
    case 'rejected':
      return IFluentDismiss24Filled;
    case 'cancelled':
      return IFluentArrowUndo24Filled;
    default:
      return IFluentCheckmark24Filled;
  }
});
</script>
