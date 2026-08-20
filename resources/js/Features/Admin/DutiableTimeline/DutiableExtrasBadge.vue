<template>
  <!--
    A bar says when someone held a seat and nothing else. These per-assignment overrides —
    a different email, a study programme, a photo — are invisible on the chart and are
    exactly what a merge or a delete would throw away, so they get a mark of their own.
  -->
  <Tooltip v-if="entries.length > 0">
    <TooltipTrigger as-child>
      <span
        class="inline-flex shrink-0 cursor-help items-center gap-0.5 rounded-sm px-0.5 text-muted-foreground hover:text-foreground"
        :aria-label="$t('dutiables.timeline.extras.title')"
      >
        <component :is="entry.icon" v-for="entry in entries" :key="entry.key" class="size-3" />
      </span>
    </TooltipTrigger>

    <TooltipContent side="right" class="max-w-72 space-y-1">
      <p class="text-[11px] font-semibold">
        {{ $t('dutiables.timeline.extras.title') }}
      </p>
      <dl class="space-y-0.5">
        <div v-for="entry in entries" :key="entry.key" class="flex gap-1.5 text-[11px]">
          <dt class="shrink-0 text-muted-foreground">
            {{ $t(`dutiables.timeline.extras.${entry.key}`) }}:
          </dt>
          <dd class="min-w-0 break-words">
            {{ entry.value }}
          </dd>
        </div>
      </dl>
    </TooltipContent>
  </Tooltip>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { AtSign, GraduationCap, Image, Tag, Text } from 'lucide-vue-next';

import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';

import type { ParsedRow } from './types';

const props = defineProps<{
  extras: ParsedRow['extras'];
}>();

/** Long prose would make the tooltip a wall; the point is "there is something here". */
const DESCRIPTION_PREVIEW = 160;

const entries = computed(() => {
  const extras = props.extras;

  if (!extras) return [];

  return [
    { key: 'email', icon: AtSign, value: extras.email },
    { key: 'study_program', icon: GraduationCap, value: extras.study_program },
    { key: 'photo', icon: Image, value: extras.photo ? $t('dutiables.timeline.extras.photo_set') : undefined },
    { key: 'original_duty_name', icon: Tag, value: extras.original_duty_name ? $t('dutiables.timeline.extras.original_duty_name_set') : undefined },
    {
      key: 'description',
      icon: Text,
      value: extras.description
        ? extras.description.slice(0, DESCRIPTION_PREVIEW) + (extras.description.length > DESCRIPTION_PREVIEW ? '…' : '')
        : undefined,
    },
  ].filter((entry): entry is { key: string; icon: typeof AtSign; value: string } => entry.value !== undefined);
});
</script>
