<template>
  <div>
    <Head>
      <title>{{ $t('search.all_page_title') }}</title>
      <meta name="description" :content="$t('search.all_page_description')">
    </Head>

    <PageTitleBand
      :eyebrow="pageEyebrow"
      :title="$t('search.all_search_title')"
      :lead="$t('search.all_search_description')"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <div class="space-y-4">
        <!-- Search Input -->
        <div class="relative min-w-0">
          <IFluentSearch16Regular class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <input
            v-model="searchInput"
            type="text"
            :placeholder="`${$t('search.all_search_placeholder')}...`"
            :class="[
              'h-11 w-full border border-border bg-background pl-10 pr-9 text-sm text-foreground',
              'placeholder:text-muted-foreground/70 transition-colors focus:border-brand focus:outline-none',
            ]"
            @keydown.enter="handleSearch"
          >
          <button
            v-if="searchInput"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            @click="handleClear"
          >
            <IFluentDismiss16Regular class="size-3.5" />
            <span class="sr-only">{{ $t('Išvalyti') }}</span>
          </button>
        </div>

        <!-- Content-type toggles -->
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="mr-1 shrink-0 text-xs font-bold uppercase tracking-wider text-muted-foreground">
            {{ $t('search.search_in') }}:
          </span>
          <button
            v-for="id in controller.allCollectionIds"
            :key="id"
            type="button"
            :aria-pressed="controller.isEnabled(id)"
            :class="[
              'inline-flex h-8 shrink-0 items-center gap-1.5 border px-3 text-xs font-bold uppercase tracking-wide transition-colors',
              controller.isEnabled(id)
                ? 'border-brand bg-brand/10 text-brand'
                : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
            ]"
            @click="controller.toggleCollection(id)"
          >
            <component :is="collectionIcon[id]" class="size-3.5" />
            <span>{{ $t(sectionMeta[id].labelKey) }}</span>
            <span class="font-mono text-muted-foreground">{{ controller.sections[id].totalHits }}</span>
          </button>
          <button
            v-if="hasCollectionFilter"
            type="button"
            class="ml-1 inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-muted-foreground transition-colors hover:text-brand"
            @click="controller.resetCollections"
          >
            <IFluentDismiss16Regular class="size-3" />
            {{ $t('search.clear_all') }}
          </button>
        </div>

        <!-- Total count -->
        <div class="flex items-center justify-between border-t border-border/40 pt-2.5 text-xs font-mono uppercase tracking-wider text-muted-foreground">
          <template v-if="!controller.isSearching.value">
            {{ $t('Rasta :count rezultatų', { count: controller.totalResultCount.value }) }}
          </template>
        </div>
      </div>

      <!-- Results -->
      <div class="mt-8">
        <!-- Initial loading skeleton (no results yet) -->
        <div v-if="controller.isSearching.value && !controller.hasAnyResults.value" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-16 border border-border/50 bg-secondary/40 animate-pulse" />
        </div>

        <!-- No results across all enabled collections -->
        <div
          v-else-if="!controller.hasAnyResults.value && controller.hasSearched.value && !controller.isSearching.value"
          class="border border-dashed border-border py-16 text-center"
        >
          <div class="mx-auto flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground">
            <IFluentSearch24Regular class="size-6" />
          </div>
          <h3 class="mt-4 text-base font-bold text-foreground">
            {{ $t('search.no_results_found') }}
          </h3>
          <p class="mx-auto mt-2 max-w-sm text-sm text-muted-foreground">
            {{ $t('search.no_results_criteria') }}
          </p>
          <div v-if="hasCollectionFilter" class="mt-6">
            <Button variant="brand-outline" size="public-sm" @click="controller.resetCollections">
              {{ $t('search.clear_all') }}
            </Button>
          </div>
        </div>

        <!-- Results grouped by collection, ordered by relevance -->
        <div v-else class="space-y-10">
          <div v-for="id in controller.orderedSections.value" :key="id">
            <div class="flex items-center justify-between border-b border-border px-4 py-3">
              <div class="flex items-center gap-2">
                <component :is="collectionIcon[id]" class="size-4 text-muted-foreground" />
                <h2 class="text-sm font-bold uppercase tracking-wide text-foreground">
                  {{ $t(sectionMeta[id].labelKey) }}
                </h2>
                <span class="font-mono text-xs text-muted-foreground">{{ controller.sections[id].totalHits }}</span>
              </div>

              <SmartLink
                v-if="sectionMeta[id].viewAll"
                :href="viewAllUrl(id)"
                class="inline-flex shrink-0 items-center gap-1 text-xs font-bold uppercase tracking-wide text-brand transition-colors hover:underline"
              >
                {{ $t('search.view_all') }}
                <IFluentArrowRight16Regular class="size-3.5" />
              </SmartLink>
            </div>

            <div class="divide-y divide-border">
              <UnifiedResultItem
                v-for="(hit, i) in controller.sections[id].hits"
                :key="`${id}-${hit.id ?? i}`"
                :collection="id"
                :doc="hit"
                :icon="collectionIcon[id]"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed, type Component } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import IFluentSearch16Regular from '~icons/fluent/search16-regular';
import IFluentSearch24Regular from '~icons/fluent/search-24-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentArrowRight16Regular from '~icons/fluent/arrow-right-16-regular';
import IFluentPeopleTeam from '~icons/fluent/people-team20-regular';
import IFluentMeeting from '~icons/fluent/conference-room20-regular';
import IFluentDocument from '~icons/fluent/document20-regular';
import IFluentNews from '~icons/fluent/news20-regular';
import IFluentPage from '~icons/fluent/document-text20-regular';
import IFluentCalendar from '~icons/fluent/calendar20-regular';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import UnifiedResultItem from '@/Components/Public/Search/UnifiedResultItem.vue';
import { Button } from '@/Components/ui/button';
import { usePublicMultiSearch, type SearchCollectionId } from '@/Composables/usePublicMultiSearch';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  initialQuery?: string;
}>();

// A larger preview than the default browse size — this page shows the top hits per
// collection and hands off to the collection's own page for anything beyond that,
// so there is no "load more" here (see the collection headers' "view all" links).
const controller = usePublicMultiSearch({ perPage: 5, filteredPerPage: 12 });

const page = usePage();

interface SectionMeta {
  labelKey: string;
  viewAll?: { routeName: string; isGlobal: boolean };
}

const sectionMeta: Record<SearchCollectionId, SectionMeta> = {
  institutions: { labelKey: 'search.section_institutions', viewAll: { routeName: 'contacts', isGlobal: false } },
  meetings: { labelKey: 'search.section_meetings', viewAll: { routeName: 'publicMeetings.index', isGlobal: false } },
  documents: { labelKey: 'search.section_documents', viewAll: { routeName: 'documents', isGlobal: true } },
  news: { labelKey: 'search.section_news', viewAll: { routeName: 'newsArchive', isGlobal: false } },
  pages: { labelKey: 'search.section_pages' },
  calendar: { labelKey: 'search.section_calendar', viewAll: { routeName: 'calendar.list', isGlobal: true } },
};

const collectionIcon: Record<SearchCollectionId, Component> = {
  institutions: IFluentPeopleTeam,
  meetings: IFluentMeeting,
  documents: IFluentDocument,
  news: IFluentNews,
  pages: IFluentPage,
  calendar: IFluentCalendar,
};

const pageEyebrow = computed(() => {
  const tenant = page.props.tenant as { shortname?: string } | undefined;
  return tenant?.shortname ?? 'VU SA';
});

const hasCollectionFilter = computed(() => controller.enabledCollections.value.length > 0);

const searchInput = computed<string>({
  get: () => controller.displayQuery.value,
  set: (value: string) => controller.search(value),
});

const handleSearch = (): void => {
  controller.search(searchInput.value, true);
};

const handleClear = (): void => {
  controller.clearFilters();
};

const viewAllUrl = (id: SearchCollectionId): string => {
  const meta = sectionMeta[id];
  if (!meta.viewAll) {
    return '#';
  }
  const locale = (page.props.app as { locale?: string })?.locale || 'lt';
  try {
    // Global routes (e.g. documents, calendar) are bound to the hardcoded `www.` domain
    // group and have no `{subdomain}` parameter; only tenant routes take one.
    const base = meta.viewAll.isGlobal
      ? route(meta.viewAll.routeName, { lang: locale })
      : route(meta.viewAll.routeName, {
          subdomain: (page.props.tenant as { subdomain?: string })?.subdomain ?? 'www',
          lang: locale,
        });

    // Carry the current search term over — the collection pages all read `q` from the URL
    // on load, so this pre-fills their search box instead of starting blank.
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

usePageBreadcrumbs(
  () => BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem($t('search.all_search_title'), undefined, IFluentSearch16Regular),
  ]),
  { placement: 'band' },
);

onMounted(() => {
  const initial = props.initialQuery?.trim();
  if (initial && initial.length >= controller.minQueryLength) {
    controller.search(initial, true);
  }
  else {
    // Browse everything so the page opens populated and the type counts are real.
    controller.search(controller.browseQuery, true);
  }
});
</script>
