<script setup lang="ts">
import { computed, ref } from 'vue';
import { transChoice } from 'laravel-vue-i18n';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { EmptyState } from '@/Components/Patterns';
import WikiPageList from '@/Features/Admin/Knowledge/WikiPageList.vue';
import WikiSearchField from '@/Features/Admin/Knowledge/WikiSearchField.vue';
import { groupByLetter, letterAnchor } from '@/Features/Admin/Knowledge/wikiAlphabet';
import { searchWiki } from '@/Features/Admin/Knowledge/wikiSearch';
import type { WikiIndexEntry } from '@/Types/learning';

const props = withDefaults(defineProps<{ index: WikiIndexEntry[]; doneIds?: string[] }>(), { doneIds: () => [] });
const done = computed(() => new Set(props.doneIds));

const query = ref('');
const groups = computed(() => groupByLetter(searchWiki(props.index, query.value)));
</script>

<template>
  <OverviewPage
    :title="$t('learning.pages.alphabet')"
    :eyebrow="$t('learning.pages.knowledge')"
    :lead="transChoice('learning.wiki.alphabet_lead', index.length, { count: String(index.length) })"
  >
    <div class="sticky top-[var(--shell-chrome-height,0px)] z-10 -mx-4 space-y-3 border-b border-border bg-background/95 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0" data-slot="alphabet-bar">
      <WikiSearchField v-model="query" class="max-w-3xl" :label="$t('learning.wiki.search_label')" :placeholder="$t('learning.wiki.search_placeholder')" />
      <nav :aria-label="$t('learning.wiki.jump_to_letter')">
        <ul class="flex gap-1 overflow-x-auto pb-1">
          <li v-for="group in groups" :key="group.letter">
            <a :href="`#${letterAnchor(group.letter)}`" class="flex size-11 shrink-0 items-center justify-center border border-border text-sm font-bold text-foreground transition-colors hover:border-foreground sm:size-9">{{ group.letter }}</a>
          </li>
        </ul>
      </nav>
    </div>

    <div class="max-w-3xl space-y-10">
      <section v-for="group in groups" :id="letterAnchor(group.letter)" :key="group.letter" class="grid scroll-mt-48 gap-x-8 sm:grid-cols-[3rem_minmax(0,1fr)]" :aria-labelledby="`${letterAnchor(group.letter)}-title`">
        <h2 :id="`${letterAnchor(group.letter)}-title`" class="u-display text-4xl leading-none text-brand">{{ group.letter }}</h2>
        <WikiPageList :pages="group.pages" :done-ids="done" class="mt-3 sm:mt-0" />
      </section>
      <EmptyState v-if="!groups.length" :title="query ? $t('learning.wiki.no_results', { query }) : $t('learning_library.knowledge.empty_title')" />
    </div>
  </OverviewPage>
</template>
