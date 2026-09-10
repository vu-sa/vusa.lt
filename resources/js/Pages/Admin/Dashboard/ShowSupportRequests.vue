<template>
  <AdminContentPage>
    <Head>
      <title>{{ $t('vusa.lt pagalba') }}</title>
    </Head>

    <div class="space-y-6">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="max-w-xl">
          <h1 class="mb-1 text-3xl font-bold tracking-tight">
            {{ $t('vusa.lt pagalba') }}
          </h1>
          <p class="text-sm text-muted-foreground">
            {{ $t('Čia galite pranešti apie vusa.lt svetainės problemas, siūlyti patobulinimus ir stebėti užklausų eigą.') }}
          </p>
        </div>

        <Button as-child>
          <Link :href="route('mySupportRequests.create')">
            <Plus class="size-4" />
            {{ $t('Naujas pranešimas') }}
          </Link>
        </Button>
      </div>

      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <Button
          v-for="status in statusOptions"
          :key="status.value"
          variant="outline"
          :class="[
            'h-auto justify-start gap-3 px-4 py-3 text-left',
            selectedStatus === status.value && 'border-primary bg-primary/5 ring-1 ring-primary',
          ]"
          :aria-pressed="selectedStatus === status.value"
          @click="toggleStatus(status.value)"
        >
          <span :class="['size-2.5 shrink-0 rounded-full', statusDotClass(status.value)]" />
          <span class="min-w-0 flex-1">
            <span class="block truncate text-xs text-muted-foreground">{{ status.label }}</span>
            <span class="block text-xl font-semibold leading-tight">{{ statusCounts[status.value] ?? 0 }}</span>
          </span>
        </Button>
      </div>

      <Tabs v-model="activeTab" class="space-y-4" @update:model-value="changeTab">
        <TabsList class="h-auto flex-wrap gap-2">
          <TabsTrigger value="all" class="gap-2">
            {{ $t('Visi') }}
            <Badge v-if="tabCounts.all" variant="secondary" size="tiny">
              {{ tabCounts.all }}
            </Badge>
          </TabsTrigger>
          <TabsTrigger value="mine" class="gap-2">
            {{ $t('Mano pranešimai') }}
            <Badge v-if="tabCounts.mine" variant="secondary" size="tiny">
              {{ tabCounts.mine }}
            </Badge>
          </TabsTrigger>
        </TabsList>

        <div class="flex flex-wrap items-center gap-3">
          <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
            <Search class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" class="pl-8" :placeholder="$t('Ieškoti pranešimų')" />
          </div>

          <Select v-model="selectedType" @update:model-value="applyFilters">
            <SelectTrigger class="w-[170px]">
              <SelectValue :placeholder="$t('Tipas')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">
                {{ $t('Visi tipai') }}
              </SelectItem>
              <SelectItem v-for="type in types" :key="type.id" :value="String(type.id)">
                {{ translatedName(type.name) }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="selectedArea" @update:model-value="applyFilters">
            <SelectTrigger class="w-[170px]">
              <SelectValue :placeholder="$t('Sritis')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">
                {{ $t('Visos sritys') }}
              </SelectItem>
              <SelectItem v-for="area in areas" :key="area.id" :value="String(area.id)">
                {{ translatedName(area.name) }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-if="assignees.length" v-model="selectedAssignee" @update:model-value="applyFilters">
            <SelectTrigger class="w-[180px]">
              <SelectValue :placeholder="$t('Priskirta')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">
                {{ $t('Visi vykdytojai') }}
              </SelectItem>
              <SelectItem v-for="assignee in assignees" :key="assignee.id" :value="assignee.id">
                {{ assignee.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <TabsContent value="all">
          <SupportRequestsTable
            :requests
            :sorting
            :status-options
            @change-page="changePage"
            @change-sorting="changeSorting"
          />
        </TabsContent>
        <TabsContent value="mine">
          <SupportRequestsTable
            :requests
            :sorting
            :status-options
            @change-page="changePage"
            @change-sorting="changeSorting"
          />
        </TabsContent>
      </Tabs>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { watchDebounced } from '@vueuse/core';
import { Plus, Search } from 'lucide-vue-next';

import SupportRequestsTable from './Partials/SupportRequestsTable.vue';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { SupportRequestItem, SupportRequestTaxonomyItem } from '@/Types/supportRequests';

type FilterValue = string | number | string[] | number[];

const props = defineProps<{
  requests: {
    data: SupportRequestItem[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
  };
  currentTab: 'all' | 'mine';
  tabCounts: { all: number; mine: number };
  statusCounts: Record<string, number>;
  filters: Record<string, FilterValue>;
  sorting: Array<{ id: string; desc: boolean }>;
  types: SupportRequestTaxonomyItem[];
  areas: SupportRequestTaxonomyItem[];
  assignees: Array<{ id: string; name: string; profile_photo_path?: string | null }>;
  statusOptions: Array<{ value: string; label: string; badgeVariant: string }>;
}>();

const page = usePage();
const locale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale ?? 'lt');
const activeTab = ref(props.currentTab);
const search = ref(typeof props.filters.search === 'string' ? props.filters.search : '');
const selectedType = ref(String(props.filters.type ?? 'all'));
const selectedArea = ref(String(props.filters.area ?? 'all'));
const selectedAssignee = ref(String(props.filters.assigned_to ?? 'all'));
const selectedStatus = ref(String(props.filters.status ?? ''));

usePageBreadcrumbs([{ label: $t('vusa.lt pagalba') }]);

const translatedName = (name: SupportRequestTaxonomyItem['name']) =>
  getTranslatedValue(name, locale.value, '—');

const statusDotClass = (status: string) => ({
  new: 'bg-sky-500',
  reviewing: 'bg-amber-500',
  planned: 'bg-zinc-500',
  in_progress: 'bg-indigo-500',
  done: 'bg-emerald-500',
  declined: 'bg-red-500',
}[status] ?? 'bg-zinc-400');

const currentFilters = (): Record<string, FilterValue> => {
  const filters: Record<string, FilterValue> = {};

  if (search.value.trim()) filters.search = search.value.trim();
  if (selectedStatus.value) filters.status = selectedStatus.value;
  if (selectedType.value !== 'all') filters.type = selectedType.value;
  if (selectedArea.value !== 'all') filters.area = selectedArea.value;
  if (selectedAssignee.value !== 'all') filters.assigned_to = selectedAssignee.value;

  return filters;
};

const visit = (overrides: Record<string, unknown> = {}) => {
  router.get(route('mySupportRequests.index'), {
    tab: activeTab.value,
    filters: JSON.stringify(currentFilters()),
    sorting: JSON.stringify(props.sorting),
    ...overrides,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const changeTab = (tab: string) => {
  activeTab.value = tab === 'mine' ? 'mine' : 'all';
  visit({ page: 1 });
};

const applyFilters = () => visit({ page: 1 });

const toggleStatus = (status: string) => {
  selectedStatus.value = selectedStatus.value === status ? '' : status;
  applyFilters();
};

const changePage = (pageNumber: number) => visit({ page: pageNumber });

const changeSorting = (sorting: Array<{ id: string; desc: boolean }>) => {
  router.get(route('mySupportRequests.index'), {
    tab: activeTab.value,
    filters: JSON.stringify(currentFilters()),
    sorting: JSON.stringify(sorting),
    page: 1,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

watchDebounced(search, () => applyFilters(), { debounce: 300 });

watch(() => props.currentTab, (tab) => {
  activeTab.value = tab;
});
</script>
