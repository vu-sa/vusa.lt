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

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 min-w-0">
        <span class="min-w-0 flex-1 truncate font-medium text-sm">
          <InflectedDutyName v-if="hit.collection === 'duties'" :name="hit.title" :matches="matches._searchTitleMatches" />
          <SearchMatch v-else-if="titleMatch" inline :match="titleMatch" />
          <template v-else>{{ hit.title }}</template>
        </span>

        <Badge v-if="hit.isRecent" variant="outline" class="shrink-0 text-xs font-medium" :title="$t('Neseniai žiūrėtas')">
          <Clock class="size-3.5" aria-hidden="true" />
          {{ $t('Neseniai') }}
        </Badge>

        <!-- Related institution indicator -->
        <Badge v-if="hit.isRelated" variant="outline" class="shrink-0 text-xs font-medium" :title="$t('Iš susijusios institucijos')">
          <LinkIcon class="size-3.5" aria-hidden="true" />
          {{ $t('Susiję') }}
        </Badge>

        <Badge v-if="hit.contextBadge" variant="outline" class="shrink-0 text-xs font-medium">
          {{ hit.contextBadge }}
        </Badge>

        <!-- Status badge -->
        <Badge v-if="hit.statusBadge" :class="['shrink-0 text-xs', toneClass(hit.statusBadge.tone)]">
          <component :is="toneIcon(hit.statusBadge.tone)" class="size-3.5" aria-hidden="true" />
          {{ hit.statusBadge.label }}
        </Badge>
      </div>
      <SearchMatch compact :match="matches._searchMatch" :title="hit.title" />
      <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5 min-w-0">
        <span v-if="hit.subtitle" class="min-w-0 truncate">{{ hit.subtitle }}</span>
        <span v-if="hit.subtitle && hit.meta" class="shrink-0 text-muted-foreground/40">•</span>
        <!-- meta may be a short date (other collections) or a long member list (duties):
             cap its width and truncate so long values never overflow the row. -->
        <span v-if="hit.meta" class="min-w-0 max-w-[55%] shrink-0 truncate tabular-nums">{{ hit.meta }}</span>
      </div>
    </div>

    <!-- View / edit quick actions (opt-in — command palette rows) -->
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

    <!-- Arrow indicator -->
    <ChevronRight
      v-else
      :class="[
        'size-4 shrink-0 transition-opacity',
        selected ? 'text-brand opacity-100' : 'text-muted-foreground/50 opacity-0 group-hover:opacity-100',
      ]"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Link as LinkIcon, Clock, Eye, Pencil } from 'lucide-vue-next';

import { toneClass, toneIcon } from '../Utils/searchBadges';
import type { NormalizedSearchHit, SearchCollectionKey } from '../Utils/searchHitMappers';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import SearchMatch from '@/Components/ui/SearchMatch.vue';
import { matchTitle, type SearchMatchDocument } from '@/Shared/Search/matches';
import { computed } from 'vue';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { Badge } from '@/Components/ui/badge';

const props = defineProps<{
  hit: NormalizedSearchHit;
  selected?: boolean;
  /** Renders View/Edit icon buttons instead of the chevron (command palette rows). */
  showActions?: boolean;
}>();

const matches = computed(() => props.hit.raw as SearchMatchDocument);
const titleMatch = computed(() => matchTitle(props.hit.title, matches.value._searchTitleMatches));

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
