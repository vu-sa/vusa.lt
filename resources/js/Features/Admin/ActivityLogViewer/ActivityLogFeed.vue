<template>
  <div>
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse space-y-2">
        <div class="flex items-center gap-2">
          <div class="h-6 w-6 rounded-full bg-zinc-200 dark:bg-zinc-700" />
          <div class="h-4 w-24 rounded bg-zinc-200 dark:bg-zinc-700" />
        </div>
        <div class="ml-8 h-10 w-full rounded bg-zinc-100 dark:bg-zinc-800" />
      </div>
    </div>

    <div v-else-if="entries.length === 0" class="py-8 text-center">
      <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('activity.empty') }}</p>
    </div>

    <div v-else class="flex flex-col gap-4">
      <div
        v-for="entry in entries"
        :key="entry.id"
        class="border-b border-zinc-200 pb-4 last:border-0 last:pb-0 dark:border-zinc-700"
      >
        <ActivityLogEntry :entry />
      </div>

      <Button v-if="hasMore" variant="outline" size="sm" class="w-full" :disabled="loadingMore" @click="$emit('load-more')">
        {{ loadingMore ? $t('activity.loading') : $t('activity.load_more') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import ActivityLogEntry from './ActivityLogEntry.vue';

import { Button } from '@/Components/ui/button';
import type { ActivityEntry } from '@/Types/activityLog';

withDefaults(defineProps<{
  entries: ActivityEntry[];
  loading?: boolean;
  loadingMore?: boolean;
  hasMore?: boolean;
}>(), {
  loading: false,
  loadingMore: false,
  hasMore: false,
});

defineEmits<{
  'load-more': [];
}>();
</script>
