<template>
  <div class="space-y-1.5 text-sm">
    <span class="font-medium text-foreground">{{ change.label }}</span>
    <div v-if="change.attached.length > 0" class="flex flex-wrap items-center gap-1">
      <Plus class="h-3.5 w-3.5 text-status-success" aria-hidden="true" />
      <span class="text-xs text-muted-foreground">{{ $t('activity.attached') }}:</span>
      <Badge v-for="item in change.attached" :key="item.id" variant="success" size="tiny">
        {{ item.label }}
      </Badge>
    </div>
    <div v-if="change.detached.length > 0" class="flex flex-wrap items-center gap-1">
      <Minus class="h-3.5 w-3.5 text-status-danger" aria-hidden="true" />
      <span class="text-xs text-muted-foreground">{{ $t('activity.detached') }}:</span>
      <Badge v-for="item in change.detached" :key="item.id" variant="destructive" size="tiny">
        {{ item.label }}
      </Badge>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Minus, Plus } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import type { ActivityRelationChange } from '@/Types/activityLog';

defineProps<{
  change: ActivityRelationChange;
}>();
</script>
