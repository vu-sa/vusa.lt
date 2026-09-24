<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="max-w-[95vw] sm:max-w-[90vw] w-full max-h-[90vh] sm:max-h-[85vh] overflow-y-auto p-4 sm:p-6">
      <DialogHeader class="pb-3">
        <DialogTitle class="text-lg sm:text-xl">
          {{ $t('Visi atstovai') }}
        </DialogTitle>
        <DialogDescription class="text-sm">
          {{ $t('Peržiūrėkite visų atstovų prisijungimo aktyvumą') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Tabs for Active vs Inactive -->
        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="w-full overflow-x-auto scrollbar-none">
            <TabsTrigger value="all" class="flex-1 whitespace-nowrap text-xs sm:text-sm">
              {{ $t('Visi') }} ({{ stats.total }})
            </TabsTrigger>
            <TabsTrigger value="active" class="flex-1 whitespace-nowrap text-xs sm:text-sm gap-1.5">
              <span class="size-1.5 bg-status-success" aria-hidden="true" />
              {{ $t('Aktyvūs') }} ({{ stats.activeLast30Days }})
            </TabsTrigger>
            <TabsTrigger value="inactive" class="flex-1 whitespace-nowrap text-xs sm:text-sm gap-1.5">
              <span class="size-1.5 bg-status-danger" aria-hidden="true" />
              {{ $t('Neaktyvūs') }} ({{ inactiveCount }})
            </TabsTrigger>
          </TabsList>

          <div class="mt-4 space-y-4">
            <div class="relative">
              <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                v-model="searchQuery"
                type="search"
                :placeholder="$t('Ieškoti atstovų...')"
                :aria-label="$t('Ieškoti atstovų...')"
                class="h-11 pl-9"
              />
            </div>

            <div v-if="isFetching" class="space-y-2">
              <Skeleton v-for="index in 5" :key="index" class="h-14" />
            </div>

            <div v-else class="max-h-[55vh] divide-y divide-border overflow-y-auto border-y border-border">
              <RepresentativeUserRow
                v-for="user in pageUsers"
                :key="user.id"
                :user
              />

              <div v-if="pageUsers.length === 0" class="py-8 text-center text-muted-foreground">
                <Users class="mx-auto mb-4 h-12 w-12 opacity-50" />
                <p>{{ searchQuery ? $t('Atstovų nerasta pagal paiešką') : $t('Atstovų nerasta') }}</p>
              </div>
            </div>

            <div v-if="pagination.total > 0" class="flex items-center justify-between gap-3 pt-2">
              <Button
                variant="outline"
                size="sm"
                voice="sentence"
                :disabled="pagination.current_page <= 1 || isFetching"
                @click="previousPage"
              >
                {{ $t('visak.activity.previous') }}
              </Button>
              <span class="text-sm text-muted-foreground">
                {{ $t('visak.activity.page_of', {
                  current: pagination.current_page,
                  last: pagination.last_page,
                }) }}
              </span>
              <Button
                variant="outline"
                size="sm"
                voice="sentence"
                :disabled="pagination.current_page >= pagination.last_page || isFetching"
                @click="nextPage"
              >
                {{ $t('visak.activity.next') }}
              </Button>
            </div>
          </div>
        </Tabs>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { watchDebounced } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Users, Search } from 'lucide-vue-next';

import type {
  RepresentativeActivityStats,
  RepresentativePageData,
} from '../types';

import RepresentativeUserRow from './RepresentativeUserRow.vue';

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';
import { useApi } from '@/Composables/useApi';

interface Props {
  tenantIds: string[];
  stats: RepresentativeActivityStats;
  isOpen: boolean;
  initialTab?: 'active' | 'inactive';
}

const props = withDefaults(defineProps<Props>(), {
  initialTab: 'inactive',
});

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
}>();

const activeTab = ref<'all' | 'active' | 'inactive'>(props.initialTab);
const searchQuery = ref('');
const page = ref(1);
let lastRequestedUrl = '';
const requestUrl = computed(() => route('api.v1.admin.visak.representatives', {
  tenant_ids: props.tenantIds,
  category: activeTab.value,
  search: searchQuery.value.trim() || undefined,
  page: page.value,
  per_page: 20,
}));
const {
  data,
  isFetching,
  execute,
} = useApi<RepresentativePageData>(requestUrl, {
  immediate: false,
});

const pageUsers = computed(() => data.value?.users ?? []);
const pagination = computed(() => data.value?.pagination ?? {
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
});
const inactiveCount = computed(() => props.stats.total - props.stats.activeLast30Days);

// Reset tab when modal opens with initialTab
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    activeTab.value = props.initialTab || 'inactive';
    searchQuery.value = '';
    page.value = 1;
    loadPage(true);
  }
});

watch(activeTab, () => {
  if (!props.isOpen) {
    return;
  }

  page.value = 1;
  loadPage();
});

watch(() => props.tenantIds, () => {
  if (!props.isOpen) {
    return;
  }

  page.value = 1;
  loadPage();
}, { deep: true });

watchDebounced(searchQuery, () => {
  if (!props.isOpen) {
    return;
  }

  page.value = 1;
  loadPage();
}, { debounce: 300, maxWait: 800 });

function previousPage(): void {
  if (page.value <= 1) {
    return;
  }

  page.value -= 1;
  loadPage();
}

function nextPage(): void {
  if (page.value >= pagination.value.last_page) {
    return;
  }

  page.value += 1;
  loadPage();
}

function loadPage(force = false): void {
  const url = requestUrl.value;

  if (!force && url === lastRequestedUrl) {
    return;
  }

  lastRequestedUrl = url;
  execute();
}
</script>
