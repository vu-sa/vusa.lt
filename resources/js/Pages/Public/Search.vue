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
      :page-switcher-query="controller.displayQuery.value"
      :total-hits="controller.totalResultCount.value"
      :is-searching="controller.isSearching.value"
      :has-active-filters="selectedCollectionCount > 0"
      show-newest-first
    >
      <!-- Search input -->
      <template #search-input>
        <BaseSearchInput
          :query="controller.displayQuery.value"
          :is-searching="controller.isSearching.value"
          :recent-searches="controller.recentSearches.value"
          :type-to-search
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
        <FilterSidebar
          :active-filter-count="selectedCollectionCount"
          mobile-title="search.search_in"
          @clear-filters="controller.resetCollections"
        >
          <template #mobile-filters>
            <Accordion type="multiple" :default-value="['collections']" class="w-full">
              <FilterAccordion
                value="collections"
                :label="$t('search.search_in')"
                :icon="Layers"
                :badge-count="selectedCollectionCount"
                variant="mobile"
              >
                <CheckboxFilter
                  :options="collectionOptions"
                  :selected-values="enabledCollectionIds"
                  :max-visible="0"
                  @toggle="(value) => controller.toggleCollection(value as SearchCollectionId)"
                >
                  <template #option-prefix="{ option }">
                    <component
                      :is="collectionIcon[option.value as SearchCollectionId]"
                      class="size-4 text-muted-foreground"
                    />
                  </template>
                </CheckboxFilter>
              </FilterAccordion>
            </Accordion>
          </template>

          <template #desktop-filters>
            <Accordion type="multiple" :default-value="['collections']" class="w-full space-y-3">
              <FilterAccordion
                value="collections"
                :label="$t('search.search_in')"
                :description="$t('search.search_in_description')"
                :icon="Layers"
                :badge-count="selectedCollectionCount"
                :is-loading="controller.isSearching.value && !controller.hasAnyResults.value"
                :skeleton-count="6"
                :icon-container-class="getFacetIconColor('Layers')"
              >
                <CheckboxFilter
                  :options="collectionOptions"
                  :selected-values="enabledCollectionIds"
                  :max-visible="0"
                  @toggle="(value) => controller.toggleCollection(value as SearchCollectionId)"
                >
                  <template #option-prefix="{ option }">
                    <component
                      :is="collectionIcon[option.value as SearchCollectionId]"
                      class="size-4 text-muted-foreground"
                    />
                  </template>
                </CheckboxFilter>
              </FilterAccordion>
            </Accordion>
          </template>
        </FilterSidebar>
      </template>

      <!-- Stacked, relevance-ordered sections -->
      <template #results>
        <!-- Initial loading skeleton (no results yet) -->
        <div v-if="controller.isSearching.value && !controller.hasAnyResults.value" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-16 rounded-md border border-border/50 bg-muted/40 animate-pulse" />
        </div>

        <!-- Flat interleaved results -->
        <div v-else class="space-y-2">
          <UnifiedResultItem
            v-for="(hit, i) in interleavedHits"
            :key="`${hit.collection}-${hit.doc.id ?? i}`"
            :collection="hit.collection"
            :doc="hit.doc"
            :icon="collectionIcon[hit.collection]"
          />

          <!-- "View all" links for collections with more results -->
          <div v-if="collectionsWithMore.length > 0" class="flex flex-wrap items-center gap-2 pt-4">
            <Button
              v-for="id in collectionsWithMore"
              :key="id"
              variant="outline"
              size="sm"
              as-child
            >
              <SmartLink :href="viewAllUrl(id)" class="gap-1.5">
                <component :is="collectionIcon[id]" class="size-3.5" />
                {{ $t(sectionMeta[id].labelKey) }}
                <Badge variant="secondary" class="px-1.5">
                  +{{ (controller.sections[id].totalHits - controller.sections[id].hits.length).toLocaleString() }}
                </Badge>
              </SmartLink>
            </Button>
          </div>

          <!-- No results across all enabled collections -->
          <div
            v-if="!controller.hasAnyResults.value && controller.hasSearched.value && !controller.isSearching.value"
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
import { onMounted, ref, type Component, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { SearchX, Layers } from 'lucide-vue-next';

import IFluentSearch16Regular from '~icons/fluent/search16-regular';
import IFluentPeopleTeam from '~icons/fluent/people-team20-regular';
import IFluentMeeting from '~icons/fluent/conference-room20-regular';
import IFluentDocument from '~icons/fluent/document20-regular';
import IFluentNews from '~icons/fluent/news20-regular';
import IFluentPage from '~icons/fluent/document-text20-regular';
import IFluentCalendar from '~icons/fluent/calendar20-regular';
import BaseSearchInterface from '@/Components/Public/Search/Shared/BaseSearchInterface.vue';
import BaseSearchInput from '@/Components/Public/Search/Shared/BaseSearchInput.vue';
import UnifiedResultItem from '@/Components/Public/Search/UnifiedResultItem.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { FilterSidebar, FilterAccordion, CheckboxFilter, getFacetIconColor } from '@/Components/Shared/Search';
import { Accordion } from '@/Components/ui/accordion';
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

// Round-robin interleave: take one hit at a time from each relevance-ordered
// section so the mixed list shows the strongest results first across all types.
const interleavedHits = computed(() => {
  const orderedIds = controller.orderedSections.value;
  const result: Array<{ collection: SearchCollectionId; doc: Record<string, unknown> }> = [];
  const maxHits = Math.max(0, ...orderedIds.map(id => controller.sections[id].hits.length));

  for (let i = 0; i < maxHits; i++) {
    for (const id of orderedIds) {
      const hit = controller.sections[id].hits[i];
      if (hit) {
        result.push({ collection: id, doc: hit });
      }
    }
  }
  return result;
});

// Collections that have more results than currently shown — surfaced as "view all" chips.
const collectionsWithMore = computed(() =>
  controller.orderedSections.value.filter(id =>
    controller.sections[id].hasMore && sectionMeta[id].viewAll,
  ),
);

// Checkbox-filter data for the collection toggles in the facet sidebar.
const collectionOptions = computed(() =>
  controller.allCollectionIds.map(id => ({
    value: id,
    label: $t(sectionMeta[id].labelKey),
    count: controller.sections[id]?.totalHits ?? 0,
  })),
);

// Checked boxes — an empty selection means "no filter, show everything" (same model as
// the document search page's content-type filter).
const enabledCollectionIds = computed(() =>
  controller.allCollectionIds.filter(id => controller.isEnabled(id)),
);

const selectedCollectionCount = computed(() => enabledCollectionIds.value.length);

const viewAllUrl = (id: SearchCollectionId): string => {
  const meta = sectionMeta[id];
  if (!meta.viewAll) {
    return '#';
  }
  const locale = (page.props.app as { locale?: string })?.locale || 'lt';
  try {
    // Global routes (e.g. documents) are bound to the hardcoded `www.` domain
    // group and have no `{subdomain}` parameter; only tenant routes take one.
    const base = meta.viewAll.isGlobal
      ? route(meta.viewAll.routeName, { lang: locale })
      : route(meta.viewAll.routeName, {
          subdomain: (page.props.tenant as { subdomain?: string })?.subdomain ?? 'www',
          lang: locale,
        });

    // Carry the current search term over — the documents/meetings/contacts pages all read
    // `q` from the URL on load, so this pre-fills their search box instead of starting blank.
    const term = controller.displayQuery.value.trim();
    if (!term) {
      return base;
    }
    // `base` is second so a relative URL (as Ziggy can return in some configs) still resolves.
    const url = new URL(base, window.location.origin);
    url.searchParams.set('q', term);
    return url.toString();
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
    controller.displayQuery.value = value;
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
  else {
    // Browse everything so the page opens populated and the sidebar counts are real.
    controller.search(controller.browseQuery, true);
  }
});
</script>
