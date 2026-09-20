<template>
  <section class="flex flex-col gap-1" data-slot="search-result-group" :data-collection="collection">
    <header class="flex items-center justify-between gap-4 border-t border-border pt-3">
      <!-- The label is rendered by EntityTypeMark, which the rule cannot see through. -->
      <!-- eslint-disable-next-line vuejs-accessibility/heading-has-content -->
      <h2>
        <EntityTypeMark :type="entityType" :label="pluralLabel" size="md" class="font-semibold" />
      </h2>
      <Link
        v-if="href"
        :href
        class="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline pointer-coarse:py-2"
      >
        {{ $t('Rodyti visus (:count)', { count: String(total) }) }}
      </Link>
    </header>

    <ul class="divide-y divide-border">
      <li v-for="hit in hits" :key="hit.id">
        <component
          :is="hitHref(hit) ? Link : 'div'"
          v-bind="hitHref(hit) ? { href: hitHref(hit) } : {}"
          class="flex min-h-11 items-center gap-3 px-1 py-3 hover:bg-secondary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
        >
          <span class="min-w-0 flex-1">
            <span class="block truncate font-medium">{{ hit.title }}</span>
            <span v-if="secondaryLine(hit)" class="block truncate text-sm text-muted-foreground">{{ secondaryLine(hit) }}</span>
          </span>
          <StatusBadge v-if="hit.statusBadge" :status="toStatus(hit.statusBadge)" class="shrink-0" />
        </component>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleAlert, CircleCheck, CircleX, Info, Link2, Minus } from 'lucide-vue-next';
import { computed } from 'vue';

import type { NormalizedSearchHit, SearchCollectionKey } from '../Utils/searchHitMappers';
import type { BadgeTone } from '../Utils/searchBadges';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { StatusBadge } from '@/Components/Patterns';
import type { StatusPresentation, StatusRole } from '@/Constants/statuses';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';

const props = defineProps<{
  collection: SearchCollectionKey;
  hits: NormalizedSearchHit[];
  /** Typesense `found`: how many exist, beyond the few rows shown. */
  total: number;
  /** Where the full list lives; null hides the link (the user may not open it). */
  href: string | null;
}>();

// The multi-search result keys are camelCase; the entity registry's are the model names.
const ENTITY_TYPE: Record<SearchCollectionKey, string> = {
  meetings: 'meeting',
  agendaItems: 'agenda_item',
  institutions: 'institution',
  resources: 'resource',
  duties: 'duty',
  documents: 'document',
  news: 'news',
  pages: 'page',
  calendar: 'calendar',
  users: 'user',
};

const entityType = computed(() => ENTITY_TYPE[props.collection]);
const pluralLabel = computed(() => getEntityTypeDefinition(entityType.value)?.pluralLabel);

// Documents' `href` is the public URL; the admin destination is the view link.
const hitHref = (hit: NormalizedSearchHit): string | undefined => hit.viewHref ?? hit.editHref ?? hit.href;

const secondaryLine = (hit: NormalizedSearchHit): string => [hit.subtitle, hit.meta].filter(Boolean).join(' · ');

const TONE_ROLE: Record<BadgeTone, { role: StatusRole; icon: StatusPresentation['icon'] }> = {
  success: { role: 'success', icon: CircleCheck },
  danger: { role: 'danger', icon: CircleX },
  warning: { role: 'attention', icon: CircleAlert },
  info: { role: 'info', icon: Info },
  neutral: { role: 'neutral', icon: Minus },
  related: { role: 'progress', icon: Link2 },
};

function toStatus(badge: NonNullable<NormalizedSearchHit['statusBadge']>): StatusPresentation {
  return { label: badge.label, ...TONE_ROLE[badge.tone] };
}
</script>
