<template>
  <div class="space-y-2 border border-border p-3 text-xs" data-testid="file-usage">
    <div class="flex items-center justify-between gap-2">
      <span class="min-w-0 break-words font-semibold text-foreground">{{ title ?? $t('files.ui.usage_title') }}</span>
      <StatusBadge :status voice="sentence" />
    </div>

    <p class="text-muted-foreground">
      {{ usage.is_safe_to_delete
        ? $t('files.messages.usage_safe', { count: usage.scanned_models.length })
        : $t('files.messages.usage_found', { count: usage.total_usages }) }}
    </p>

    <ul v-if="usage.usage_details.length > 0" class="max-h-60 divide-y divide-border overflow-y-auto border-t border-border">
      <li v-for="detail in usage.usage_details" :key="`${detail.model_class}:${detail.id}`">
        <component
          :is="detail.url ? Link : 'div'"
          :href="detail.url ?? undefined"
          :class="[
            'flex min-h-11 flex-col justify-center py-1.5',
            detail.url && 'hover:bg-secondary focus-visible:bg-secondary focus-visible:outline-none',
          ]"
        >
          <span class="truncate font-medium text-foreground">{{ detail.title }}</span>
          <span class="text-muted-foreground">
            {{ $t(`files.usage.models.${detail.model_type}`) }}
            <template v-if="(detail.matched_parts_count ?? 0) > 1">
              · {{ $t('files.usage.matched_blocks', { count: detail.matched_parts_count ?? 0 }) }}
            </template>
          </span>
        </component>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleCheck, Link2 } from '@lucide/vue';

import type { FileUsageResult } from '../useFileUsageScan';

import { StatusBadge } from '@/Components/Patterns';
import type { StatusPresentation } from '@/Constants/statuses';

const props = defineProps<{
  usage: FileUsageResult;
  title?: string;
}>();

const status = computed<StatusPresentation>(() => props.usage.is_safe_to_delete
  ? { label: $t('files.ui.usage_safe_badge'), role: 'success', icon: CircleCheck }
  : { label: $t('files.ui.usage_used_badge'), role: 'attention', icon: Link2 });
</script>
