<template>
  <div class="flex items-center gap-3 w-full min-w-0" data-slot="search-hit-row" :data-collection="hit.collection">
    <div
      v-if="hit.imageUrl && !hit.isRecent"
      class="size-10 shrink-0 overflow-hidden border border-border bg-muted"
    >
      <img :src="hit.imageUrl" :alt="hit.title" class="size-full object-cover">
    </div>
    <div v-else-if="hit.isRecent" class="flex size-10 shrink-0 items-center justify-center bg-secondary text-muted-foreground">
      <Clock class="size-5" aria-hidden="true" />
    </div>
    <EntityTypeMark v-else :type="collectionEntityType[hit.collection]" size="lg" icon-only />

    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 min-w-0">
        <span class="min-w-0 flex-1 truncate text-sm font-bold">
          <InflectedDutyName v-if="hit.collection === 'duties'" :name="hit.title" :matches="matches._searchTitleMatches" />
          <SearchMatch v-else :match="titleMatch" :title="hit.title" />
        </span>

        <span v-if="hit.aside" class="shrink-0 text-xs tabular-nums text-muted-foreground" data-slot="search-hit-aside">{{ hit.aside }}</span>

        <Badge v-if="hit.isRecent" variant="outline" class="shrink-0 text-xs font-medium" :title="$t('Neseniai žiūrėtas')">
          <Clock class="size-3.5" aria-hidden="true" />
          {{ $t('Neseniai') }}
        </Badge>

        <Badge v-if="hit.isRelated" variant="outline" class="shrink-0 text-xs font-medium" :title="$t('Iš susijusios institucijos')">
          <LinkIcon class="size-3.5" aria-hidden="true" />
          {{ $t('Susiję') }}
        </Badge>

        <Badge v-if="hit.contextBadge" variant="outline" class="shrink-0 text-xs font-medium">
          {{ hit.contextBadge }}
        </Badge>

        <InactiveInstitutionBadge v-if="hit.inactiveInstitution" :active="false" />

        <StatusBadge v-if="hit.statusBadge" :status="hit.statusBadge" class="shrink-0" />
      </div>
      <!-- One context line: the match explains the row in place of its subtitle, never as an extra line. -->
      <div class="mt-0.5 flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
        <span v-if="showType && typeLabel" class="shrink-0 font-medium text-foreground/80" data-slot="search-hit-type">{{ typeLabel }}</span>
        <span v-if="showType && typeLabel && (contextMatch || hit.subtitle || hit.meta)" class="shrink-0 text-muted-foreground/40" aria-hidden="true">·</span>
        <span v-if="contextMatch || hit.subtitle" class="min-w-0 truncate">
          <SearchMatch :match="contextMatch" :title="hit.subtitle" />
        </span>
        <span v-if="(contextMatch || hit.subtitle) && hit.meta" class="shrink-0 text-muted-foreground/40" aria-hidden="true">·</span>
        <!-- Long member lists must not push the context outside the row. -->
        <span v-if="hit.meta" class="min-w-0 max-w-[55%] shrink-0 truncate tabular-nums">
          <SearchMatch :match="metaMatch" :title="hit.meta" />
        </span>
      </div>
    </div>

    <div v-if="showActions" class="flex shrink-0 items-center gap-1">
      <button
        v-if="hit.viewHref"
        type="button"
        :class="quickActionClasses"
        :title="$t('Peržiūrėti')"
        :aria-label="$t('Peržiūrėti')"
        @click.stop="$emit('view')"
      >
        <Eye class="size-4" />
      </button>
      <button
        v-if="hit.editHref"
        type="button"
        :class="quickActionClasses"
        :title="$t('Redaguoti')"
        :aria-label="$t('Redaguoti')"
        @click.stop="$emit('edit')"
      >
        <Pencil class="size-4" />
      </button>
    </div>

    <ChevronRight
      v-else-if="trailing === 'chevron'"
      :class="[
        'size-4 shrink-0 transition-opacity',
        selected ? 'text-brand opacity-100' : 'text-muted-foreground/50 opacity-0 group-hover:opacity-100',
      ]"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Link as LinkIcon, Clock, Eye, Pencil } from '@lucide/vue';
import { computed } from 'vue';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import SearchMatch from '@/Components/ui/SearchMatch.vue';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { StatusBadge } from '@/Components/Patterns';
import { Badge } from '@/Components/ui/badge';

import { useSearchHitMatches } from '../Composables/useSearchHitMatches';
import type { NormalizedSearchHit, SearchCollectionKey } from '../Utils/searchHitMappers';

const props = withDefaults(defineProps<{
  hit: NormalizedSearchHit;
  selected?: boolean;
  trailing?: 'chevron' | 'none';
  /** Renders View/Edit icon buttons instead of the chevron (command palette rows). */
  showActions?: boolean;
  /** Names the record type on the context line, for lists that mix collections. */
  showType?: boolean;
}>(), {
  trailing: 'chevron',
});

const { matches, titleMatch, metaMatch, contextMatch } = useSearchHitMatches(() => props.hit);
const typeLabel = computed(() => {
  const definition = getEntityTypeDefinition(collectionEntityType[props.hit.collection]);
  return definition ? $t(definition.label) : undefined;
});

defineEmits<{
  view: [];
  edit: [];
}>();

const collectionEntityType: Record<SearchCollectionKey, string> = {
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

const quickActionClasses = [
  'flex size-9 items-center justify-center text-muted-foreground opacity-100 transition-colors',
  'hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring',
  'pointer-coarse:size-11 md:opacity-0 md:group-hover:opacity-100 md:group-focus-within:opacity-100',
];
</script>
