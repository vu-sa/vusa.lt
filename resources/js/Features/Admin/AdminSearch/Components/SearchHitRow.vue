<template>
  <div class="flex items-center gap-3 w-full min-w-0">
    <!-- Icon / image container -->
    <div
      v-if="hit.imageUrl && !hit.isRecent"
      class="size-9 shrink-0 overflow-hidden rounded-lg bg-muted ring-1 ring-border"
    >
      <img :src="hit.imageUrl" :alt="hit.title" class="size-full object-cover">
    </div>
    <div
      v-else
      :class="[
        'flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors',
        hit.isRecent
          ? 'bg-zinc-400/10 text-zinc-500 group-hover:bg-zinc-400/15 dark:bg-zinc-500/15 dark:text-zinc-400 dark:group-hover:bg-zinc-500/25'
          : [colorClasses.bg, colorClasses.text, colorClasses.hoverBg, colorClasses.darkBg, colorClasses.darkText, colorClasses.darkHoverBg],
      ]"
    >
      <component :is="hit.icon" class="size-4" />
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2">
        <span class="font-medium truncate text-sm">
          {{ hit.title }}
        </span>

        <!-- Recent indicator -->
        <span
          v-if="hit.isRecent"
          class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-zinc-500/10 text-zinc-600 ring-1 ring-inset ring-zinc-500/20 dark:bg-zinc-500/20 dark:text-zinc-400"
          :title="$t('Neseniai žiūrėtas')"
        >
          <Clock class="size-2.5" />
          {{ $t('Neseniai') }}
        </span>

        <!-- Related institution indicator -->
        <span
          v-if="hit.isRelated"
          class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-purple-500/10 text-purple-600 ring-1 ring-inset ring-purple-500/20 dark:bg-purple-500/20 dark:text-purple-400"
          :title="$t('Iš susijusios institucijos')"
        >
          <LinkIcon class="size-2.5" />
          {{ $t('Susiję') }}
        </span>

        <!-- Status badge -->
        <Badge v-if="hit.statusBadge" :class="['shrink-0', toneClass(hit.statusBadge.tone)]">
          {{ hit.statusBadge.label }}
        </Badge>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
        <span v-if="hit.subtitle" class="truncate">{{ hit.subtitle }}</span>
        <span v-if="hit.subtitle && hit.meta" class="text-muted-foreground/40">•</span>
        <span v-if="hit.meta" class="shrink-0 tabular-nums">{{ hit.meta }}</span>
      </div>
    </div>

    <!-- Arrow indicator -->
    <ChevronRight
      :class="[
        'size-4 shrink-0 transition-opacity',
        selected ? 'text-primary opacity-100' : 'text-muted-foreground/40 opacity-0 group-hover:opacity-100',
      ]"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Link as LinkIcon, Clock } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { getCollectionColor } from '../Utils/searchHitMappers';
import { toneClass } from '../Utils/searchBadges';
import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

const props = defineProps<{
  hit: NormalizedSearchHit;
  selected?: boolean;
}>();

const colorClasses = computed(() => getCollectionColor(props.hit.collection));
</script>
