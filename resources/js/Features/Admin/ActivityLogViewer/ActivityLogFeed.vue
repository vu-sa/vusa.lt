<template>
  <div>
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse space-y-2">
        <div class="flex items-center gap-2">
          <div class="h-6 w-6 bg-muted" />
          <div class="h-4 w-24 bg-muted" />
        </div>
        <div class="ml-8 h-10 w-full bg-muted/60" />
      </div>
    </div>

    <div v-else-if="entries.length === 0" class="py-8 text-center">
      <p class="text-sm text-muted-foreground">
        {{ $t('activity.empty') }}
      </p>
    </div>

    <div v-else class="flex flex-col gap-4">
      <div
        v-for="entry in entries"
        :key="entry.id"
        class="border-b border-border pb-4 last:border-0 last:pb-0"
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
