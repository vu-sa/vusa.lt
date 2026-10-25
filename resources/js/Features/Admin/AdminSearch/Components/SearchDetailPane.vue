<template>
  <div class="flex h-full flex-col" data-slot="search-detail-pane">
    <EmptyState
      v-if="!hit || !entry"
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
      <component :is="entry.component" :key="hit.id" v-bind="entry.props(hit)" class="min-h-0 flex-1" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { MousePointerClick } from '@lucide/vue';
import { computed } from 'vue';

import { previewRegistry } from '../Config/previewRegistry';
import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import { EmptyState } from '@/Components/Patterns';
import SearchMatch from '@/Components/ui/SearchMatch.vue';
import type { SearchMatchDocument } from '@/Shared/Search/matches';

const props = defineProps<{
  hit: NormalizedSearchHit | null;
}>();

const entry = computed(() => (props.hit ? previewRegistry[props.hit.collection] : null));
const excerpt = computed(() => (props.hit?.raw as SearchMatchDocument | undefined)?._searchMatch);
</script>
