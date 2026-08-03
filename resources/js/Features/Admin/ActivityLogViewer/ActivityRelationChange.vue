<template>
  <div class="space-y-1.5 text-sm">
    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ change.label }}</span>
    <div v-if="change.attached.length > 0" class="flex flex-wrap items-center gap-1">
      <Plus class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
      <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('activity.attached') }}:</span>
      <Badge v-for="item in change.attached" :key="item.id" variant="success" size="tiny">
        {{ item.label }}
      </Badge>
    </div>
    <div v-if="change.detached.length > 0" class="flex flex-wrap items-center gap-1">
      <Minus class="h-3.5 w-3.5 text-red-600 dark:text-red-400" />
      <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('activity.detached') }}:</span>
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
