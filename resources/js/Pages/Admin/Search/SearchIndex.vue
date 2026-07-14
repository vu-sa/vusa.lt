<template>
  <AdminContentPage :title="$t('Paieška')">
    <template #create-button>
      <!-- Reserved-height wrapper: keeps the action area stable across tabs
           (empty on "all"/"agenda-items", widest on "duties") so it never jumps. -->
      <div class="flex min-h-9 items-center justify-end gap-2">
        <Button v-if="activeTab === 'meetings' && can.create.meetings" @click="showMeetingModal = true">
          <Plus class="mr-2 size-4" />
          {{ $t('Naujas posėdis') }}
        </Button>
        <Link v-else-if="activeTab === 'institutions' && can.create.institutions" :href="route('institutions.create')">
          <Button>
            <Plus class="mr-2 size-4" />
            {{ $t('Nauja institucija') }}
          </Button>
        </Link>
        <Link v-else-if="activeTab === 'resources' && can.create.resources" :href="route('resources.create')">
          <Button>
            <Plus class="mr-2 size-4" />
            {{ $t('Sukurti išteklių') }}
          </Button>
        </Link>
        <Link v-else-if="activeTab === 'documents' && can.create.documents" :href="route('documents.index')">
          <Button>
            <Plus class="mr-2 size-4" />
            {{ $t('Dokumentai') }}
          </Button>
        </Link>
        <Link v-else-if="activeTab === 'news' && can.create.news" :href="route('news.create')">
          <Button>
            <Plus class="mr-2 size-4" />
            {{ $t('Nauja naujiena') }}
          </Button>
        </Link>
        <Link v-else-if="activeTab === 'pages' && can.create.pages" :href="route('pages.create')">
          <Button>
            <Plus class="mr-2 size-4" />
            {{ $t('Naujas puslapis') }}
          </Button>
        </Link>
        <Link v-else-if="activeTab === 'calendar' && can.create.calendar" :href="route('calendar.create')">
          <Button>
            <Plus class="mr-2 size-4" />
            {{ $t('Naujas įvykis') }}
          </Button>
        </Link>
        <Link v-else-if="activeTab === 'users' && can.create.users" :href="route('users.create')">
          <Button>
            <Plus class="mr-2 size-4" />
            {{ $t('Naujas narys') }}
          </Button>
        </Link>
        <div v-else-if="activeTab === 'duties'" class="flex items-center gap-2">
          <Link :href="route('duties.updateUsersWizard')">
            <Button variant="outline">
              <Users class="mr-2 size-4" />
              {{ $t('forms.fields.duty_user_wizard') }}
            </Button>
          </Link>
          <Link v-if="can.create.duties" :href="route('duties.create')">
            <Button>
              <Plus class="mr-2 size-4" />
              {{ $t('Nauja pareigybė') }}
            </Button>
          </Link>
        </div>
      </div>
    </template>

    <div class="flex flex-col gap-3">
      <!-- Search bar -->
      <div class="relative">
        <Search class="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted-foreground" />
        <input
          ref="searchInputRef"
          v-model="q"
          type="text"
          :placeholder="$t('Ieškoti visur...')"
          class="h-12 w-full rounded-xl border bg-background pl-12 pr-12 text-base shadow-sm outline-none transition-shadow placeholder:text-muted-foreground/60 focus:ring-2 focus:ring-primary/20"
        >
        <button
          v-if="q"
          type="button"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
          :aria-label="$t('Išvalyti')"
          @click="q = ''; searchInputRef?.focus()"
        >
          <X class="size-4" />
        </button>
      </div>

      <!-- Tabs -->
      <div v-if="isConfigLoading" class="flex gap-2 border-b pb-2">
        <div v-for="i in 4" :key="i" class="h-8 w-24 animate-pulse rounded-md bg-muted/50" />
      </div>
      <SearchTabs v-else v-model="activeTab" :tabs class="border-b" />

      <!-- Panel area (bounded height for internal scroll) -->
      <div class="flex h-[calc(100dvh-19rem)] min-h-[360px] flex-col">
        <SearchAllPanel
          v-if="activeTab === 'all'"
          :results="multiResults"
          :query="q"
          :is-searching
          :has-searched
          :error
          :duty-ctx="dutyMapperCtx"
        />
        <SearchCollectionPanel
          v-else-if="activeCollectionTab"
          :key="activeCollectionTab.value"
          :collection="activeCollectionTab.collection"
          :query="q"
          :empty-message="activeCollectionTab.emptyMessage"
        />
      </div>
    </div>

    <NewMeetingDialog v-if="showMeetingModal" :show-modal="showMeetingModal" @close="showMeetingModal = false" />
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, defineAsyncComponent } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useDebounceFn } from '@vueuse/core';
import { Search, X, Plus, Users } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Button } from '@/Components/ui/button';
import SearchTabs, { type SearchTab } from '@/Features/Admin/AdminSearch/Components/SearchTabs.vue';
import SearchAllPanel from '@/Features/Admin/AdminSearch/Components/SearchAllPanel.vue';
import SearchCollectionPanel from '@/Features/Admin/AdminSearch/Components/SearchCollectionPanel.vue';
import type { AdminCollection } from '@/Features/Admin/AdminSearch/Types/AdminSearchTypes';
import type { MapperContext } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { useAdminSearch } from '@/Composables/useAdminSearch';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type { MultiSearchResults } from '@/Shared/Search/types';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';

const NewMeetingDialog = defineAsyncComponent(() => import('@/Components/Dialogs/NewMeetingDialog.vue'));

defineProps<{
  can: {
    create: {
      meetings: boolean;
      institutions: boolean;
      resources: boolean;
      duties: boolean;
      documents: boolean;
      news: boolean;
      pages: boolean;
      calendar: boolean;
      users: boolean;
    };
  };
}>();

usePageBreadcrumbs([{ label: $t('Paieška'), icon: Search }]);

interface CollectionTabDef {
  value: string;
  label: string;
  collection: AdminCollection;
  /** Key into MultiSearchResults.counts for the tab badge. */
  countKey: keyof MultiSearchResults['counts'];
  emptyMessage: string;
}

const collectionTabs = computed<CollectionTabDef[]>(() => [
  {
    value: 'meetings',
    label: $t('Posėdžiai'),
    collection: 'meetings',
    countKey: 'meetings',
    emptyMessage: $t('Nerasta posėdžių pagal jūsų paiešką'),
  },
  {
    value: 'agenda-items',
    label: $t('Punktai'),
    collection: 'agenda_items',
    countKey: 'agendaItems',
    emptyMessage: $t('Nerasta darbotvarkės punktų pagal jūsų paiešką'),
  },
  {
    value: 'institutions',
    label: $t('Institucijos'),
    collection: 'institutions',
    countKey: 'institutions',
    emptyMessage: $t('Nerasta institucijų pagal jūsų paiešką'),
  },
  {
    value: 'resources',
    label: $t('Ištekliai'),
    collection: 'resources',
    countKey: 'resources',
    emptyMessage: $t('Nerasta išteklių pagal jūsų paiešką'),
  },
  {
    value: 'duties',
    label: $t('Pareigybės'),
    collection: 'duties',
    countKey: 'duties',
    emptyMessage: $t('Nerasta pareigybių pagal jūsų paiešką'),
  },
  {
    value: 'documents',
    label: $t('Dokumentai'),
    collection: 'documents',
    countKey: 'documents',
    emptyMessage: $t('Nerasta dokumentų pagal jūsų paiešką'),
  },
  {
    value: 'news',
    label: $t('Naujienos'),
    collection: 'news',
    countKey: 'news',
    emptyMessage: $t('Nerasta naujienų pagal jūsų paiešką'),
  },
  {
    value: 'pages',
    label: $t('Puslapiai'),
    collection: 'pages',
    countKey: 'pages',
    emptyMessage: $t('Nerasta puslapių pagal jūsų paiešką'),
  },
  {
    value: 'calendar',
    label: $t('Kalendorius'),
    collection: 'calendar',
    countKey: 'calendar',
    emptyMessage: $t('Nerasta kalendoriaus įvykių pagal jūsų paiešką'),
  },
  {
    value: 'users',
    label: $t('Nariai'),
    collection: 'users',
    countKey: 'users',
    emptyMessage: $t('Nerasta narių pagal jūsų paiešką'),
  },
]);

const adminSearch = useAdminSearch();
const isConfigLoading = computed(() => adminSearch.config.value === null && !adminSearch.configError.value);

// Context for mappers that need user-relative state (cross-tenant duties + related-institution badges).
const dutyMapperCtx = computed<MapperContext>(() => ({
  ownTenantIds: adminSearch.getCollectionTenantIds('duties'),
  isSuperAdmin: adminSearch.isSuperAdmin.value,
  directInstitutionIds: [
    ...adminSearch.getDirectInstitutionIds('meetings'),
    ...adminSearch.getDirectInstitutionIds('agenda_items'),
  ],
}));

const visibleCollectionTabs = computed(() =>
  collectionTabs.value.filter(tab => adminSearch.hasCollectionAccess(tab.collection)),
);

// URL-backed state (?q= and ?tab=)
const initialParams = typeof window === 'undefined'
  ? new URLSearchParams()
  : new URLSearchParams(window.location.search);

const q = ref(initialParams.get('q') || '');
const activeTab = ref(initialParams.get('tab') || 'all');
const searchInputRef = ref<HTMLInputElement | null>(null);
const showMeetingModal = ref(false);

const activeCollectionTab = computed(() =>
  collectionTabs.value.find(tab => tab.value === activeTab.value),
);

const multiResults = ref<MultiSearchResults>(createEmptyMultiSearchResults());
const isSearching = ref(false);
const hasSearched = ref(false);
const error = ref<string | null>(null);

const runMultiSearch = useDebounceFn(async (query: string) => {
  if (adminSearch.isRateLimited.value) {
    error.value = $t('Per daug užklausų. Palaukite ir bandykite vėliau.');
    isSearching.value = false;
    return;
  }
  isSearching.value = true;
  error.value = null;
  try {
    multiResults.value = await adminSearch.multiSearch(query, {
      meetingsLimit: 12,
      agendaItemsLimit: 12,
      institutionsLimit: 12,
      resourcesLimit: 12,
      dutiesLimit: 12,
      newsLimit: 6,
      pagesLimit: 6,
      calendarLimit: 6,
      documentsLimit: 6,
      usersLimit: 12,
    });
    hasSearched.value = true;
  }
  catch (err) {
    const message = err instanceof Error ? err.message : 'Search failed';
    error.value = message.includes('Too many requests')
      ? $t('Per daug užklausų. Palaukite ir bandykite vėliau.')
      : message;
  }
  finally {
    isSearching.value = false;
  }
}, 300);

const tabs = computed<SearchTab[]>(() => {
  const allCount = Object.values(multiResults.value.counts).reduce((a, b) => a + b, 0);
  const collectionTabsWithCounts = visibleCollectionTabs.value.map(tab => ({
    value: tab.value,
    label: tab.label,
    count: hasSearched.value ? multiResults.value.counts[tab.countKey] : undefined,
  }));
  // Dynamic reorder: sort collection tabs by hit count descending after each search.
  // The "All" tab stays pinned at position 0.
  if (hasSearched.value) {
    collectionTabsWithCounts.sort((a, b) => (b.count ?? 0) - (a.count ?? 0));
  }
  return [
    { value: 'all', label: $t('Viskas'), count: hasSearched.value ? allCount : undefined },
    ...collectionTabsWithCounts,
  ];
});

const writeUrl = () => {
  if (typeof window === 'undefined') {
    return;
  }
  const params = new URLSearchParams();
  if (q.value.trim()) {
    params.set('q', q.value.trim());
  }
  if (activeTab.value !== 'all') {
    params.set('tab', activeTab.value);
  }
  const queryString = params.toString();
  window.history.replaceState({}, '', `${window.location.pathname}${queryString ? `?${queryString}` : ''}`);
};

watch(activeTab, writeUrl);

watch(q, () => {
  // Collection tabs sync the URL through their own controller after searching.
  if (activeTab.value === 'all') {
    writeUrl();
  }
  isSearching.value = true;
  runMultiSearch(q.value);
});

// Fall back to the All tab when the requested tab is inaccessible.
watch(
  () => adminSearch.config.value,
  (config) => {
    if (!config) {
      return;
    }
    const current = activeCollectionTab.value;
    if (current && !adminSearch.hasCollectionAccess(current.collection)) {
      activeTab.value = 'all';
    }
  },
);

onMounted(async () => {
  searchInputRef.value?.focus();
  await adminSearch.initialize();
  isSearching.value = true;
  runMultiSearch(q.value);
});
</script>
