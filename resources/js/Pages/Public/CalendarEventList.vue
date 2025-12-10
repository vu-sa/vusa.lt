<template>
  <div class="px-4 py-12 lg:px-8">
    <!-- Header section -->
    <header class="mb-8 border-b border-zinc-200 pb-6 dark:border-zinc-700">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">
            {{ $t("Visų renginių sąrašas") }}
          </h1>
          <p class="mt-2 max-w-3xl text-zinc-600 dark:text-zinc-400">
            {{ $t("Vilniaus universiteto Studentų atstovybės ir studentų (-čių) bendruomenės renginių sąrašas") }}.
          </p>
        </div>
        <div class="flex gap-2">
          <Button variant="secondary" as="a" :href="route('calendar.ics', { lang: $page.props.app.locale })">
            <IFluentCalendarLtr20Regular />
            {{ $t("iCalendar") }}
          </Button>
          <Button variant="secondary" @click="showModal = true">
            <IFluentArrowSync20Regular />
            {{ $t("Sinchronizuoti") }}
          </Button>
        </div>
      </div>

      <!-- Filter section -->
      <div class="mb-6 flex flex-col gap-4">
        <!-- Filter section -->
        <div class="flex flex-wrap items-center pt-3 gap-2">
          <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $t("Filtruoti pagal") }}:</span>

          <div class="grid grid-cols-2 gap-2">
            <!-- Category filter -->
            <Select v-model="selectedCategory" @update:model-value="onCategoryChange">
              <SelectTrigger class="min-w-[150px] h-8 text-sm">
                <SelectValue :placeholder="$t('Kategorija')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__all__">{{ $t("Visos kategorijos") }}</SelectItem>
                <SelectItem v-for="option in categoryOptions" :key="option.value" :value="String(option.value)">
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <!-- Tenant filter -->
            <Select v-model="selectedTenant" @update:model-value="onTenantChange">
              <SelectTrigger class="min-w-[150px] h-8 text-sm">
                <SelectValue :placeholder="$t('Padalinys')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__all__">{{ $t("Visi padaliniai") }}</SelectItem>
                <SelectItem v-for="option in tenantOptions" :key="option.value" :value="String(option.value)">
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Search input with action buttons -->
        <div class="flex gap-2">
          <div class="relative flex-grow">
            <input v-model="filters.search" type="text" :placeholder="`${$t('Ieškoti pagal pavadinimą')}...`"
              class="w-full rounded-md border border-zinc-300 py-1.5 pl-9 pr-3 text-sm text-zinc-800 placeholder:text-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder:text-zinc-500"
              @keyup.enter="applyFilters">
            <IFluentSearch16Regular class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 dark:text-zinc-500" />
          </div>

          <!-- Search button -->
          <Button :disabled="searchLoading" @click="applyFilters">
            <IFluentSearch20Filled />
            {{ $t("Ieškoti") }}
          </Button>

          <!-- Reset filters button -->
          <Button v-if="filters.search || filters.category || filters.tenant" variant="secondary" @click="resetFilters">
            <IFluentDelete20Regular />
            {{ $t("Išvalyti") }}
          </Button>
        </div>
      </div>
    </header>

    <!-- Tabs for upcoming and past events -->
    <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
      <TabsList class="mb-4">
        <TabsTrigger value="upcoming">{{ $t('Būsimi renginiai') }}</TabsTrigger>
        <TabsTrigger value="past">{{ $t('Praėję renginiai') }}</TabsTrigger>
      </TabsList>
      
      <TabsContent value="upcoming">
        <EventListContent :events="events" :tab="activeTab" @page-change="handlePageChange" />
      </TabsContent>

      <TabsContent value="past">
        <EventListContent :events="events" :tab="activeTab" @page-change="handlePageChange" />
      </TabsContent>
    </Tabs>
  </div>

  <!-- Calendar Sync Modal -->
  <CalendarSyncModal v-model:show-modal="showModal" @close="showModal = false" />
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import CalendarSyncModal from "@/Components/Modals/CalendarSyncModal.vue";
import EventListContent from "@/Components/Calendar/EventListContent.vue";

const props = defineProps<{
  events: {
    data: App.Entities.Calendar[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    path: string;
    links: any[];
  };
  activeTab: string;
  allCategories?: Array<{id: number, name: string}>;
  allTenants?: Array<{id: number, shortname: string}>;
}>();

const showModal = ref(false);
const searchLoading = ref(false);

// Parse URL parameters on component mount
const params = new URLSearchParams(window.location.search);

// Parse category and tenant params
const categoryParamStr = params.get('category');
const tenantParamStr = params.get('tenant');
const categoryParam = categoryParamStr ? parseInt(categoryParamStr) : null;
const tenantParam = tenantParamStr ? parseInt(tenantParamStr) : null;

// Filter state initialized from URL params
const filters = ref({
  search: params.get('search') || '',
  category: categoryParam,
  tenant: tenantParam,
  tab: props.activeTab || 'upcoming'
});

// Computed values for Select components (Shadcn Select uses string values)
// Use __all__ as sentinel value since SelectItem cannot have empty string value
const selectedCategory = computed({
  get: () => filters.value.category ? String(filters.value.category) : '__all__',
  set: (val: string) => {
    filters.value.category = val && val !== '__all__' ? parseInt(val) : null;
  }
});

const selectedTenant = computed({
  get: () => filters.value.tenant ? String(filters.value.tenant) : '__all__',
  set: (val: string) => {
    filters.value.tenant = val && val !== '__all__' ? parseInt(val) : null;
  }
});

// Handlers for select changes
const onCategoryChange = (value: unknown) => {
  if (typeof value === 'string') {
    selectedCategory.value = value;
    applyFilters();
  }
};

const onTenantChange = (value: unknown) => {
  if (typeof value === 'string') {
    selectedTenant.value = value;
    applyFilters();
  }
};

// Fetch all available categories and tenants from the backend rather than relying on filtered data
const page = usePage();
const allCategories = ref<{ label: string, value: number }[]>([]);
const allTenants = ref<{ label: string, value: number }[]>([]);

// Initialize with all categories and tenants from the backend
const initializeFilters = () => {
  // Reset the filter options
  allCategories.value = [];
  allTenants.value = [];
  
  // Add categories from backend
  if (props.allCategories) {
    props.allCategories.forEach(category => {
      allCategories.value.push({
        label: category.name,
        value: category.id
      });
    });
  }
  
  // Add tenants from backend
  if (props.allTenants) {
    props.allTenants.forEach(tenant => {
      allTenants.value.push({
        label: tenant.shortname,
        value: tenant.id
      });
    });
  }
};

// Initialize on component mount
initializeFilters();

// This function is no longer needed since we're getting filter options from the backend
// based on the active tab. The backend will handle the logic of which options to show.

// Use all extracted filters instead of just the current filtered events
const categoryOptions = computed(() => allCategories.value);
const tenantOptions = computed(() => allTenants.value);

// Apply filters using Inertia router
const applyFilters = () => {
  searchLoading.value = true;

  // Update URL with filter parameters
  router.visit(route('calendar.list', {
    lang: usePage().props.app.locale,
    search: filters.value.search || undefined,
    category: filters.value.category || undefined,
    tenant: filters.value.tenant || undefined,
    tab: filters.value.tab,
    page: 1 // Reset to first page when applying new filters
  }), {
    only: ['events', 'activeTab', 'disablePageTransition', 'allCategories', 'allTenants'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Re-initialize filter options from backend
      initializeFilters();
      searchLoading.value = false;
    },
    onError: () => {
      searchLoading.value = false;
    }
  });
};

// Handle tab change
const handleTabChange = (tabValue: string | number) => {
  // Update tab in filters and reset other filters when changing tabs
  filters.value = {
    search: '',
    category: null,
    tenant: null,
    tab: String(tabValue)
  };

  // Apply filters with the new tab (all filters reset)
  applyFilters();
};

// Handle page change
const handlePageChange = (page: number) => {
  // Update URL with the page parameter
  router.visit(route('calendar.list', {
    lang: usePage().props.app.locale,
    search: filters.value.search || undefined,
    category: filters.value.category || undefined,
    tenant: filters.value.tenant || undefined,
    tab: filters.value.tab,
    page: page
  }), {
    only: ['events', 'activeTab', 'allCategories', 'allTenants'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // We don't need to reinitialize filters when just changing pages
      // The tab hasn't changed so filter options should remain the same
    }
  });
};

// Function to reset all filters
const resetFilters = () => {
  filters.value = {
    search: '',
    category: null,
    tenant: null,
    tab: filters.value.tab // Preserve the active tab
  };
  applyFilters();
};

</script>
