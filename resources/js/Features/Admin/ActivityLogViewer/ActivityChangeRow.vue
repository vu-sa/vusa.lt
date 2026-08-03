<template>
  <div class="rounded-md bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800/50">
    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ change.label }}</span>
    <div class="mt-0.5 text-zinc-500 dark:text-zinc-400">
      <template v-if="change.type === 'diff'">
        <!-- Raw old_display/new_display, not the empty-value-substituted
             computeds below: a null old must reach ActivityTextDiff as null
             so it renders as an all-insertion, not as a struck-through
             em dash. -->
        <ActivityTextDiff :old="change.old_display" :new="change.new_display" />
      </template>
      <template v-else-if="change.type === 'rich'">
        <span class="italic">{{ $t('activity.rich_updated') }}</span>
      </template>
      <template v-else-if="change.type === 'boolean' || change.type === 'enum'">
        <Badge variant="outline" class="mr-1">{{ oldDisplay }}</Badge>
        <ArrowRight class="mx-1 inline h-3.5 w-3.5 text-zinc-400" />
        <Badge variant="outline" class="ml-1">{{ newDisplay }}</Badge>
      </template>
      <template v-else>
        <span>{{ oldDisplay }}</span>
        <ArrowRight class="mx-1 inline h-3.5 w-3.5 text-zinc-400" />
        <span>{{ newDisplay }}</span>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import type { ActivityChange } from '@/Types/activityLog';

// Lazy-loaded: ActivityLogSheet (and this row) is statically imported by
// most admin show/edit pages, but the `diff` package it pulls in should only
// ever load for the pages/users that actually render a diff row.
const ActivityTextDiff = defineAsyncComponent(() => import('./ActivityTextDiff.vue'));

const props = defineProps<{
  change: ActivityChange;
}>();

// old_display/new_display are already fully formatted server-side (locale-
// aware dates, resolved relation names, translated enum options, the plain-
// text diff projection, ...) -- this component only arranges them, it does
// not format values itself (the 'diff' branch passes them through to
// ActivityTextDiff instead of computing anything here). See
// App\Services\ActivityChangeFormatter for why that split exists.
const emptyValue = $t('activity.empty_value');
const oldDisplay = computed(() => props.change.old_display ?? emptyValue);
const newDisplay = computed(() => props.change.new_display ?? emptyValue);
</script>
