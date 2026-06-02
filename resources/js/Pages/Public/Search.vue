<template>
  <div>
    <Head>
      <title>{{ $t('search.all_page_title') }}</title>
      <meta name="description" :content="$t('search.all_page_description')">
    </Head>

    <BaseSearchInterface
      page="search"
      title-key="search.all_search_title"
      description-key="search.all_search_description"
      result-singular-key="search.result_singular"
      result-plural-key="search.result_plural"
      result-short-label=""
      no-results-key="search.no_results_found"
      browse-prompt-key="search.all_search_prompt"
      browse-prompt-mobile-key="search.all_search_prompt"
      :search-query="controller.query.value"
      :total-hits="controller.totalResultCount.value"
      :is-searching="controller.isSearching.value"
      :has-active-filters="false"
    >
      <!-- Search input -->
      <template #search-input>
        <BaseSearchInput
          :query="controller.query.value"
          :is-searching="controller.isSearching.value"
          :recent-searches="controller.recentSearches.value"
          :type-to-search="typeToSearch"
          placeholder-key="search.all_search_placeholder"
          @update:query="handleQueryUpdate"
          @search="handleSearch"
          @select-recent="handleSearch"
          @clear="handleClear"
          @update:type-to-search="(v) => (typeToSearch = v)"
          @remove-recent="controller.removeRecentSearch"
          @clear-all-history="controller.clearRecentSearches"
        />
      </template>

      <!-- Content-type toggles -->
      <template #facet-sidebar>
        <div class="rounded-lg border border-border/50 bg-card p-3 sm:p-4">
          <h2 class="text-xs font-medium uppercase tracking-wide text-muted-foreground mb-3">
            {{ $t('search.search_in') }}
          </h2>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="id in controller.allCollectionIds"
              :key="id"
              type="button"
              role="checkbox"
              :aria-checked="controller.isEnabled(id)"
              :class="[
                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-sm font-medium',
                'transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40',
                controller.isEnabled(id)
                  ? 'border-primary/30 bg-primary/10 text-primary'
                  : 'border-border/60 text-muted-foreground hover:bg-accent hover:text-foreground',
              ]"
              @click="controller.toggleCollection(id)"
            >
              <component
                :is="collectionIcon[id]"
                class="size-4"
                :class="controller.isEnabled(id) ? 'opacity-90' : 'opacity-60'"
              />
              <span>{{ $t(sectionMeta[id].labelKey) }}</span>
            </button>
          </div>
        </div>
      </template>

      <!-- Stacked, relevance-ordered sections -->
      <template #results>
        <!-- Initial loading skeleton (no results yet) -->
        <div v-if="controller.isSearching.value && !controller.hasAnyResults.value" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-16 rounded-md border border-border/50 bg-muted/40 animate-pulse" />
        </div>

        <!-- Prompt when nothing searched yet -->
        <div
          v-else-if="!controller.hasAnyResults.value && !isQueryLongEnough"
          class="text-center py-16"
        >
          <SearchIcon class="w-12 h-12 mx-auto text-muted-foreground/50 mb-4" />
          <p class="text-sm font-medium text-foreground">
            {{ $t('search.all_search_prompt') }}
          </p>
        </div>

        <!-- Results sections -->
        <div v-else class="space-y-8">
          <section
            v-for="id in controller.orderedSections.value"
            :key="id"
            class="scroll-mt-6"
          >
            <!-- Section header -->
            <div class="flex items-center justify-between mb-3">
              <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                <component :is="collectionIcon[id]" class="size-5 text-muted-foreground" />
                {{ $t(sectionMeta[id].labelKey) }}
                <span class="text-xs font-normal text-muted-foreground">
                  {{ controller.sections[id].totalHits.toLocaleString() }}
                </span>
              </h2>
              <SmartLink
                v-if="sectionMeta[id].viewAll"
                :href="viewAllUrl(id)"
                class="plain flex items-center gap-1 text-xs font-medium text-primary hover:text-primary/80 transition-colors"
              >
                {{ $t('search.view_all') }}
                <ArrowRight class="size-3" />
              </SmartLink>
            </div>

            <!-- Section body: existing per-type result wrappers -->
            <InstitutionResults
              v-if="id === 'institutions'"
              :results="controller.sections.institutions.hits"
              view-mode="list"
              :min-height="false"
              :total-hits="controller.sections.institutions.totalHits"
              :has-more-results="controller.sections.institutions.hasMore"
              :is-loading-more="controller.sections.institutions.isLoadingMore"
              :has-query="true"
              :search-query="controller.query.value"
              @load-more="controller.loadMore('institutions')"
            />
            <MeetingResults
              v-else-if="id === 'meetings'"
              :results="controller.sections.meetings.hits"
              :min-height="false"
              :total-hits="controller.sections.meetings.totalHits"
              :has-more-results="controller.sections.meetings.hasMore"
              :is-loading-more="controller.sections.meetings.isLoadingMore"
              :has-query="true"
              :search-query="controller.query.value"
              @load-more="controller.loadMore('meetings')"
            />
            <DocumentResults
              v-else-if="id === 'documents'"
              :results="controller.sections.documents.hits"
              view-mode="compact"
              :min-height="false"
              :total-hits="controller.sections.documents.totalHits"
              :has-more-results="controller.sections.documents.hasMore"
              :is-loading-more="controller.sections.documents.isLoadingMore"
              :has-query="true"
              :search-query="controller.query.value"
              @load-more="controller.loadMore('documents')"
            />
            <GenericResults
              v-else
              :type="id"
              :results="controller.sections[id].hits"
              :min-height="false"
              :total-hits="controller.sections[id].totalHits"
              :has-more-results="controller.sections[id].hasMore"
              :is-loading-more="controller.sections[id].isLoadingMore"
              :has-query="true"
              :search-query="controller.query.value"
              :no-results-title-key="sectionMeta[id].labelKey"
              @load-more="controller.loadMore(id)"
            />
          </section>

          <!-- No results across all enabled collections -->
          <div
            v-if="!controller.hasAnyResults.value && isQueryLongEnough && !controller.isSearching.value"
            class="text-center py-16"
          >
            <SearchX class="w-12 h-12 mx-auto text-muted-foreground/50 mb-4" />
            <p class="text-sm font-medium text-foreground">
              {{ $t('search.no_results_found') }}
            </p>
            <p class="text-xs text-muted-foreground mt-1">
              {{ $t('search.no_results_criteria') }}
            </p>
          </div>
        </div>
      </template>
    </BaseSearchInterface>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, type Component } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowRight, SearchX } from 'lucide-vue-next';
import SearchIcon from '~icons/fluent/search24-regular';
import IFluentSearch16Regular from '~icons/fluent/search16-regular';
import IFluentPeopleTeam from '~icons/fluent/people-team20-regular';
import IFluentMeeting from '~icons/fluent/conference-room20-regular';
import IFluentDocument from '~icons/fluent/document20-regular';
import IFluentNews from '~icons/fluent/news20-regular';
import IFluentPage from '~icons/fluent/document-text20-regular';
import IFluentCalendar from '~icons/fluent/calendar20-regular';

import BaseSearchInterface from '@/Components/Public/Search/Shared/BaseSearchInterface.vue';
import BaseSearchInput from '@/Components/Public/Search/Shared/BaseSearchInput.vue';
import DocumentResults from '@/Components/Public/Search/DocumentResults.vue';
import MeetingResults from '@/Components/Public/Search/MeetingResults.vue';
import InstitutionResults from '@/Components/Public/Search/InstitutionResults.vue';
import GenericResults from '@/Components/Public/Search/GenericResults.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';

import { usePublicMultiSearch, type SearchCollectionId } from '@/Composables/usePublicMultiSearch';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  initialQuery?: string;
}>();

const controller = usePublicMultiSearch({ perPage: 3 });
const typeToSearch = ref(true);

const page = usePage();

interface SectionMeta {
  labelKey: string;
  viewAll?: { routeName: string; isGlobal: boolean };
}

const sectionMeta: Record<SearchCollectionId, SectionMeta> = {
  institutions: { labelKey: 'search.section_institutions', viewAll: { routeName: 'contacts', isGlobal: false } },
  meetings: { labelKey: 'search.section_meetings', viewAll: { routeName: 'publicMeetings.index', isGlobal: false } },
  documents: { labelKey: 'search.section_documents', viewAll: { routeName: 'documents', isGlobal: true } },
  news: { labelKey: 'search.section_news' },
  pages: { labelKey: 'search.section_pages' },
  calendar: { labelKey: 'search.section_calendar' },
};

const collectionIcon: Record<SearchCollectionId, Component> = {
  institutions: IFluentPeopleTeam,
  meetings: IFluentMeeting,
  documents: IFluentDocument,
  news: IFluentNews,
  pages: IFluentPage,
  calendar: IFluentCalendar,
};

const isQueryLongEnough = computed(() => controller.query.value.trim().length >= controller.minQueryLength);

const viewAllUrl = (id: SearchCollectionId): string => {
  const meta = sectionMeta[id];
  if (!meta.viewAll) {
    return '#';
  }
  const locale = (page.props.app as { locale?: string })?.locale || 'lt';
  try {
    // Global routes (e.g. documents) are bound to the hardcoded `www.` domain
    // group and have no `{subdomain}` parameter; only tenant routes take one.
    if (meta.viewAll.isGlobal) {
      return route(meta.viewAll.routeName, { lang: locale });
    }
    const subdomain = (page.props.tenant as { subdomain?: string })?.subdomain ?? 'www';
    return route(meta.viewAll.routeName, { subdomain, lang: locale });
  }
  catch {
    return '#';
  }
};

const handleQueryUpdate = (value: string): void => {
  if (typeToSearch.value) {
    controller.search(value);
  }
  else {
    controller.query.value = value;
  }
};

const handleSearch = (value: string): void => {
  controller.search(value, true);
};

const handleClear = (): void => {
  controller.clearFilters();
};

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem($t('search.all_search_title'), undefined, IFluentSearch16Regular),
  ]),
);

onMounted(() => {
  // Wider content area, consistent with the document search page.
  page.props.layoutWidth = 'content';

  const initial = props.initialQuery?.trim();
  if (initial && initial.length >= controller.minQueryLength) {
    controller.search(initial, true);
  }
});
</script>
