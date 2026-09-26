<template>
  <FormPanel :title="$t('analytics.title')" :icon="Eye" flush class="text-card-foreground">
    <template v-if="isPartial" #action>
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger as-child>
            <button type="button" class="text-muted-foreground hover:text-foreground">
              <Info class="size-3.5" />
            </button>
          </TooltipTrigger>
          <TooltipContent class="max-w-xs">
            {{ $t('analytics.partial_tooltip', { date: dataSinceLabel }) }}
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </template>

    <div v-if="isFetching" class="grid grid-cols-2 divide-x divide-border">
      <div class="p-4">
        <Skeleton class="mb-1 h-7 w-16" />
        <Skeleton class="h-3 w-20" />
      </div>
      <div class="p-4">
        <Skeleton class="mb-1 h-7 w-16" />
        <Skeleton class="h-3 w-20" />
      </div>
    </div>

    <div v-else-if="!data?.available || !data.totals" class="p-4 text-xs text-muted-foreground">
      {{ $t('analytics.unavailable_title') }}
    </div>

    <div v-else class="grid grid-cols-2 divide-x divide-border">
      <div class="p-4">
        <span class="block font-mono text-2xl font-bold tracking-tight text-foreground">{{ data.totals.pageviews }}</span>
        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{{ $t('analytics.pageviews') }}</span>
      </div>
      <div class="p-4">
        <span class="block font-mono text-2xl font-bold tracking-tight text-foreground">{{ data.totals.visitors }}</span>
        <span class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{{ $t('analytics.visitors') }}</span>
      </div>
    </div>
  </FormPanel>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Eye, Info } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import { FormPanel } from '@/Components/Patterns';
import { Skeleton } from '@/Components/ui/skeleton';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { useApi } from '@/Composables/useApi';
import type { ContentAnalyticsData } from '@/Types/api.d';

const props = defineProps<{
  type: 'news' | 'page';
  id: number | string;
  /** publish_time, falling back to created_at. Used only to decide whether to warn that
   *  the figures predate tracking. */
  contentDate?: string | null;
}>();

const { data, isFetching } = useApi<ContentAnalyticsData>(
  route('api.v1.admin.analytics.content', { type: props.type, id: props.id }),
  // The card states its own unavailable case; a toast on every edit page when Umami is
  // down would be noise.
  { showErrorToast: false },
);

const dataSinceLabel = computed(() => data.value?.dataSince ?? '');

/**
 * Content created before tracking began has views we never recorded, so the total is a
 * floor rather than a lifetime figure. Only then is the warning worth the visual noise.
 */
const isPartial = computed(() => {
  if (!props.contentDate || !data.value?.dataSince) {
    return false;
  }

  return new Date(props.contentDate) < new Date(data.value.dataSince);
});
</script>
