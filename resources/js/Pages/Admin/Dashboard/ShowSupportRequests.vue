<template>
  <CollectionPage
    :source
    collection="supportRequests"
    entity-type="support_request"
    :eyebrow="$t('vusa.lt pagalba')"
    :title="$t('vusa.lt pagalba')"
    :lead="$t('Čia galite pranešti apie vusa.lt svetainės problemas, siūlyti patobulinimus ir stebėti užklausų eigą.')"
    default-view="rows"
    :available-views="['rows']"
    :item-key="item => item.id"
    :quick-filters="tabs"
    @quick-filter="changeTab"
  >
    <template #actions>
      <Button as-child variant="brand" size="lg">
        <Link :href="route('mySupportRequests.create')">
          <Plus aria-hidden="true" />
          {{ $t('Naujas pranešimas') }}
        </Link>
      </Button>
    </template>

    <template #row="{ item }">
      <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center">
        <div class="min-w-0 flex-1">
          <CollectionPrimaryCell
            :title="item.title"
            :href="route('supportRequests.show', item.id)"
            :sub="`${translatedName(item.type?.name)} · ${translatedName(item.area?.name)}`"
          />
          <p class="mt-2 text-xs text-muted-foreground">
            {{ item.creator?.name ?? item.reporter_name ?? $t('Svečias') }} · {{ formatDate(item.created_at) }}
          </p>
        </div>
        <StatusBadge :status="statusOf(item)" class="shrink-0" />
      </article>
    </template>

    <template #empty>
      <EmptyState
        :title="$t('Pranešimų nerasta')"
        :description="$t('Pakeisk filtrus arba pateik naują pranešimą.')"
        :action-label="$t('Naujas pranešimas')"
        :action-href="route('mySupportRequests.create')"
      />
    </template>
  </CollectionPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus } from 'lucide-vue-next';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { EmptyState, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useDatabaseCollectionSource } from '@/Composables/useCollectionSource';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { supportRequestStatuses } from '@/Constants/statuses';
import type { SupportRequestItem, SupportRequestTaxonomyItem } from '@/Types/supportRequests';

const props = defineProps<{
  requests: { data: SupportRequestItem[]; total: number; per_page: number; current_page: number; last_page: number };
  currentTab: 'all' | 'mine';
  tabCounts: { all: number; mine: number };
  types: SupportRequestTaxonomyItem[];
  areas: SupportRequestTaxonomyItem[];
  assignees: Array<{ id: string; name: string }>;
}>();

const page = usePage();
const locale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale ?? 'lt');
const translatedName = (name: SupportRequestTaxonomyItem['name'] | undefined): string => getTranslatedValue(name, locale.value, '—');
const tabs = computed(() => [
  { id: 'all', label: `${$t('Visi')} · ${props.tabCounts.all}`, active: props.currentTab === 'all' },
  { id: 'mine', label: `${$t('Mano pranešimai')} · ${props.tabCounts.mine}`, active: props.currentTab === 'mine' },
]);

const source = useDatabaseCollectionSource<SupportRequestItem>({
  endpoint: route('api.v1.admin.supportRequests.index'),
  initial: {
    items: props.requests.data,
    total: props.requests.total,
    perPage: props.requests.per_page,
    currentPage: props.requests.current_page,
    lastPage: props.requests.last_page,
  },
  preserveUrlKeys: ['tab'],
  defaultSort: 'created_at:desc',
  sortOptions: [
    { value: 'created_at:desc', label: $t('Naujausi') },
    { value: 'created_at:asc', label: $t('Seniausi') },
    { value: 'title:asc', label: $t('Pagal pavadinimą') },
  ],
  facets: [
    { field: 'status', label: $t('Būsena'), values: Object.entries(supportRequestStatuses).map(([value, status]) => ({ value, label: status.label })) },
    { field: 'type', label: $t('Tipas'), values: props.types.map(type => ({ value: String(type.id), label: translatedName(type.name) })) },
    { field: 'area', label: $t('Sritis'), values: props.areas.map(area => ({ value: String(area.id), label: translatedName(area.name) })) },
    { field: 'assigned_to', label: $t('Priskirta'), values: props.assignees.map(user => ({ value: user.id, label: user.name })) },
  ],
});

function statusOf(item: SupportRequestItem) {
  const value = typeof item.status === 'string' ? item.status : item.status.value;
  return supportRequestStatuses[value as keyof typeof supportRequestStatuses];
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value));
}

function changeTab(tab: string): void {
  router.get(route('mySupportRequests.index'), { tab }, { preserveState: false });
}
</script>
