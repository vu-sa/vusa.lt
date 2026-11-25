<template>
  <div class="flex h-full flex-col" data-slot="search-detail-pane">
    <EmptyState
      v-if="!hit"
      mode="empty"
      :icon="MousePointerClick"
      :title="$t('Pasirink rezultatą')"
      :description="$t('Peržiūra bus rodoma čia')"
      class="m-auto"
    />

    <template v-else>
      <p v-if="excerpt" class="line-clamp-2 border-b border-border px-5 py-3 text-sm text-muted-foreground">
        <SearchMatch :match="excerpt" />
      </p>
      <component :is="entry.component" v-if="entry" :key="hit.id" v-bind="entry.props(hit)" class="min-h-0 flex-1" />
      <SearchHitRow v-else :hit trailing="none" class="p-4" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { MousePointerClick } from '@lucide/vue';
import { computed } from 'vue';

import { EmptyState } from '@/Components/Patterns';
import SearchMatch from '@/Components/ui/SearchMatch.vue';
import type { SearchMatchDocument } from '@/Search/core/matches';

import { adminSearchCollections, type NormalizedSearchHit } from './adminSearchCollections';
import SearchHitRow from './SearchHitRow.vue';

const props = defineProps<{
  hit: NormalizedSearchHit | null;
}>();

const entry = computed(() => (props.hit ? adminSearchCollections[props.hit.collection].preview : null));
const excerpt = computed(() => (props.hit?.raw as SearchMatchDocument | undefined)?._searchMatch);
</script>
