<script setup lang="ts">
import { computed, ref } from 'vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { EmptyState } from '@/Components/Patterns';
import WikiGraph from '@/Features/Admin/Knowledge/WikiGraph.vue';
import WikiSearchField from '@/Features/Admin/Knowledge/WikiSearchField.vue';
import { searchWiki } from '@/Features/Admin/Knowledge/wikiSearch';
import type { WikiGraphPayload } from '@/Types/learning';

const props = withDefaults(defineProps<{ graph: WikiGraphPayload; doneIds?: string[] }>(), { doneIds: () => [] });

const query = ref('');
const selected = ref<string | null>(null);
const graphRef = ref<InstanceType<typeof WikiGraph> | null>(null);

const matches = computed(() => (query.value.trim() ? searchWiki(props.graph.nodes, query.value) : null));
const matchIds = computed(() => (matches.value ? new Set(matches.value.map(node => node.id)) : null));

function focus(id: string) {
  selected.value = id;
  graphRef.value?.centreOn(id);
}
</script>

<template>
  <OverviewPage :title="$t('learning.pages.graph')" :eyebrow="$t('learning.pages.knowledge')" :lead="$t('learning.wiki.graph_lead')">
    <div v-if="graph.nodes.length" class="flex flex-col gap-3">
      <WikiSearchField v-model="query" class="max-w-3xl" :label="$t('learning.wiki.search_label')" :placeholder="$t('learning.wiki.search_placeholder')" />
      <ul v-if="matches?.length" class="flex flex-wrap gap-1.5" data-slot="wiki-graph-matches">
        <li v-for="node in matches.slice(0, 8)" :key="node.id">
          <button type="button" class="inline-flex min-h-9 items-center border border-border px-2.5 text-sm text-foreground hover:border-foreground pointer-coarse:min-h-11" @click="focus(node.id)">
            {{ node.title }}
          </button>
        </li>
      </ul>
      <p v-else-if="matches" class="text-sm text-muted-foreground">
        {{ $t('learning.wiki.no_results', { query }) }}
      </p>
      <WikiGraph ref="graphRef" v-model:selected="selected" :graph :done-ids="doneIds" :match-ids="matchIds" />
    </div>
    <EmptyState v-else :title="$t('learning.wiki.graph_empty')" />
  </OverviewPage>
</template>
