<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice } from 'laravel-vue-i18n';
import { ArrowRight, CheckCheck } from '@lucide/vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { EmptyState } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import WikiPageList from '@/Features/Admin/Knowledge/WikiPageList.vue';
import WikiSearchField from '@/Features/Admin/Knowledge/WikiSearchField.vue';
import WikiProgressSummary from '@/Features/Admin/Knowledge/WikiProgressSummary.vue';
import { groupByLetter, letterAnchor } from '@/Features/Admin/Knowledge/wikiAlphabet';
import { searchWiki } from '@/Features/Admin/Knowledge/wikiSearch';
import LearningStepRow from '@/Features/Admin/Learning/LearningStepRow.vue';
import { LearningImportance } from '@/Types/enums';
import type { KnowledgeItemData, LearningDocumentsData, WikiIndexEntry, WikiPageSummary } from '@/Types/learning';

const props = defineProps<{
  knowledge: LearningDocumentsData;
  index: WikiIndexEntry[];
  sections: { id: string; title: string; description: string; order: number }[];
  canCreate?: boolean;
  doneIds?: string[];
}>();
const done = computed(() => new Set(props.doneIds ?? []));

const SECTION_PREVIEW = 5;
const FOR_YOU_LIMIT = 5;

const query = ref('');
const results = computed(() => searchWiki(props.index, query.value));
const sectionTitles = computed(() => new Map(props.sections.map(section => [section.id, section.title])));
const sectionOf = (summary: WikiPageSummary) => {
  const entry = props.index.find(candidate => candidate.id === summary.id);
  return entry?.sectionId ? sectionTitles.value.get(entry.sectionId) ?? null : null;
};

const resolved = (item: KnowledgeItemData) => item.state === 'done' || item.state === 'skipped';
const forYou = computed(() => props.knowledge.items
  .filter(item => !resolved(item) && item.importance !== LearningImportance.Optional)
  .sort((a, b) => Number(b.importance === LearningImportance.Essential) - Number(a.importance === LearningImportance.Essential))
  .slice(0, FOR_YOU_LIMIT));

const expanded = ref<Set<string>>(new Set());
const portals = computed(() => [
  ...[...props.sections].sort((a, b) => a.order - b.order).map(section => ({ ...section, pages: props.index.filter(entry => entry.sectionId === section.id) })),
  { id: 'other', title: $t('learning.wiki.unsorted'), description: '', order: Number.MAX_SAFE_INTEGER, pages: props.index.filter(entry => !entry.sectionId) },
].filter(section => section.pages.length));
const visiblePages = (section: { id: string; pages: WikiIndexEntry[] }) => expanded.value.has(section.id) ? section.pages : section.pages.slice(0, SECTION_PREVIEW);
function expand(id: string): void {
  expanded.value = new Set([...expanded.value, id]);
}

const recent = computed(() => [...props.index]
  .filter(entry => entry.updatedAt)
  .sort((a, b) => (b.updatedAt ?? '').localeCompare(a.updatedAt ?? ''))
  .slice(0, 5));
const letters = computed(() => groupByLetter(props.index).map(group => group.letter));

</script>

<template>
  <OverviewPage :title="$t('learning.pages.knowledge')" :lead="$t('learning.wiki.lead')">
    <template #actions>
      <WikiProgressSummary :done="done.size" :total="index.length" />
    </template>
    <div class="max-w-3xl space-y-3">
      <WikiSearchField v-model="query" :label="$t('learning.wiki.search_label')" :placeholder="$t('learning.wiki.search_placeholder')" />
      <p v-if="query.trim()" class="text-sm text-muted-foreground" aria-live="polite">
        {{ results.length ? transChoice('learning.wiki.results', results.length, { count: String(results.length) }) : $t('learning.wiki.no_results', { query: query.trim() }) }}
      </p>
    </div>

    <section v-if="query.trim()" :aria-label="$t('learning.wiki.search_label')" data-slot="wiki-results" class="max-w-3xl">
      <WikiPageList :pages="results" :section-of="sectionOf" />
    </section>

    <template v-else>
      <section v-if="knowledge.items.length" aria-labelledby="wiki-for-you" class="space-y-3" data-slot="wiki-for-you">
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
          <div>
            <h2 id="wiki-for-you" class="text-xl font-bold leading-tight text-foreground sm:text-2xl">{{ $t('learning.wiki.for_you') }}</h2>
          </div>
          <Button variant="outline" voice="sentence" size="sm" data-testid="wiki-for-you-all" as-child>
            <Link :href="route('knowledgeBase.mine')">{{ $t('learning.wiki.for_you_all') }}<ArrowRight class="size-4" aria-hidden="true" /></Link>
          </Button>
        </div>
        <ul v-if="forYou.length" class="divide-y divide-border border border-border">
          <LearningStepRow v-for="item in forYou" :key="item.id" :step="item" />
        </ul>
        <p v-else class="flex items-center gap-2 border-y border-border py-3 text-sm text-muted-foreground">
          <CheckCheck class="size-4 shrink-0 text-foreground" aria-hidden="true" />{{ $t('learning.wiki.for_you_done') }}
        </p>
      </section>

      <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_18rem] lg:gap-x-14">
        <section aria-labelledby="wiki-sections" class="min-w-0 space-y-6" data-slot="wiki-sections">
          <h2 id="wiki-sections" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.wiki.sections') }}</h2>
          <div class="grid gap-x-10 gap-y-10 md:grid-cols-2">
            <section v-for="section in portals" :id="`section-${section.id}`" :key="section.id" class="min-w-0 scroll-mt-24" :aria-labelledby="`portal-${section.id}`">
              <h3 :id="`portal-${section.id}`" class="border-t-2 border-foreground pt-3 text-lg font-bold leading-tight text-foreground">{{ section.title }}</h3>
              <p v-if="section.description" class="mt-1 text-sm text-muted-foreground">{{ section.description }}</p>
              <WikiPageList :pages="visiblePages(section)" dense :done-ids="done" class="mt-3" />
              <Button
                v-if="section.pages.length > SECTION_PREVIEW && !expanded.has(section.id)"
                variant="ghost"
                voice="sentence"
                size="sm"
                class="mt-1 -ml-3"
                @click="expand(section.id)"
              >
                {{ $t('learning.wiki.section_more', { count: String(section.pages.length) }) }}
              </Button>
            </section>
          </div>
          <EmptyState v-if="!portals.length" :title="$t('learning_library.knowledge.empty_title')" :description="$t('learning_library.knowledge.empty_description')" />
        </section>

        <aside class="space-y-10" data-slot="wiki-aside">
          <section v-if="recent.length" aria-labelledby="wiki-recent" class="space-y-3">
            <h2 id="wiki-recent" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.wiki.recent') }}</h2>
            <WikiPageList :pages="recent" dense :done-ids="done" />
          </section>
          <nav v-if="letters.length" aria-labelledby="wiki-letters" class="space-y-3">
            <h2 id="wiki-letters" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.pages.alphabet') }}</h2>
            <ul class="flex flex-wrap gap-1">
              <li v-for="letter in letters" :key="letter">
                <Link :href="`${route('knowledgeItems.alphabet')}#${letterAnchor(letter)}`" class="flex size-11 items-center justify-center border border-border text-sm font-bold text-foreground transition-colors hover:border-foreground sm:size-9">{{ letter }}</Link>
              </li>
            </ul>
          </nav>
        </aside>
      </div>
    </template>
  </OverviewPage>
</template>
