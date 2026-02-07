<template>
  <div class="mt-6 sm:mt-8">
    <!-- Hero Header with gradient accent -->
    <header class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100 p-6 sm:p-8 dark:from-zinc-900 dark:to-zinc-800">
      <!-- Decorative elements -->
      <div class="absolute -right-20 -top-20 size-64 rounded-full bg-vusa-red/5 blur-3xl" />
      <div class="absolute -bottom-10 -left-10 size-48 rounded-full bg-vusa-yellow/5 blur-3xl" />
      
      <div class="relative">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <SmartLink 
              :href="route('contacts', { subdomain: 'www', lang: $page.props.app.locale })"
              class="mb-3 inline-flex items-center gap-1.5 text-xs font-medium text-zinc-500 hover:text-vusa-red transition-colors dark:text-zinc-400"
            >
              ← {{ $t('Visi kontaktai') }}
            </SmartLink>
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-50 sm:text-4xl">
              {{ $t(pageTitle) }}
              <span v-if="isMainTenant && !showAllTenants" class="text-vusa-red">· {{ $t('Centriniai') }}</span>
              <span v-else-if="currentTenantName && !showAllTenants" class="text-vusa-red">· {{ currentTenantName }}</span>
              <span v-else-if="showAllTenants" class="text-vusa-red">· {{ $t('Visi padaliniai') }}</span>
            </h1>
            <p v-if="pageDescription" class="mt-2 max-w-xl text-zinc-600 dark:text-zinc-400" v-html="pageDescription" />
          </div>

          <!-- Search and filter controls -->
          <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-3">
            <div class="min-w-[200px]">
              <Label for="search" class="mb-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                {{ $t('Ieškoti') }}
              </Label>
              <div class="relative">
                <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-400" />
                <Input
                  id="search"
                  v-model="search"
                  :placeholder="$t('Vardas arba institucija...')"
                  class="pl-9 bg-white dark:bg-zinc-800"
                />
              </div>
            </div>
            <div>
              <Label for="padalinys" class="mb-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                {{ $t('Padalinys') }}
              </Label>
              <!-- Show toggle for all tenants when viewing a specific category -->
              <div v-if="categoryType" class="flex gap-2">
                <PadalinysSelector id="padalinys" size="medium" main-tenant-label="Centriniai" />
                <Button
                  :variant="showAllTenants ? 'default' : 'outline'"
                  size="default"
                  @click="toggleAllTenants"
                  :class="[
                    'whitespace-nowrap gap-1.5',
                    showAllTenants ? 'bg-vusa-red hover:bg-vusa-red/90' : 'border-dashed hover:border-vusa-red hover:text-vusa-red'
                  ]"
                >
                  <GlobeIcon class="size-4" />
                  {{ $t('Visi') }}
                </Button>
              </div>
              <PadalinysSelector v-else id="padalinys" size="medium" main-tenant-label="Centriniai" />
            </div>
          </div>
        </div>

        <!-- Stats bar -->
        <div class="mt-6 flex flex-wrap gap-6 border-t border-zinc-200/50 pt-4 dark:border-zinc-700/50">
          <div class="text-sm text-zinc-500 dark:text-zinc-400">
            <strong class="text-zinc-900 dark:text-zinc-100">{{ totalInstitutions }}</strong> {{ $t('institucijų') }}
          </div>
          <div class="text-sm text-zinc-500 dark:text-zinc-400">
            <strong class="text-zinc-900 dark:text-zinc-100">{{ totalContacts }}</strong> {{ $t('atstovų') }}
          </div>
        </div>
      </div>
    </header>

    <!-- Results -->
    <div v-if="hasResults" class="space-y-10">
      <!-- Types with multiple institutions (with headers) -->
      <section v-for="institutionType in multiInstitutionTypes" :key="institutionType.id">
        <!-- Type header -->
        <div class="mb-5 flex items-center gap-3">
          <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
            {{ $t(institutionType.title) }}
          </h2>
          <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
          <span class="text-sm text-zinc-400">
            {{ institutionType.institutions.length }}
          </span>
          <!-- Link to category page when viewing multiple types -->
          <SmartLink
            v-if="isStudentRepsPage && institutionType.slug"
            :href="route('contacts.category', {
              subdomain: 'www',
              lang: $page.props.app.locale,
              type: institutionType.slug,
            }) + '?all=1'"
            class="text-xs font-medium text-vusa-red hover:underline"
          >
            {{ $t('Žiūrėti visus') }} →
          </SmartLink>
        </div>

        <!-- Institutions grid - team style cards -->
        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
          <SmartLink
            v-for="institution in institutionType.institutions"
            :key="institution.id"
            :href="route('contacts.institution', {
              institution: institution.id,
              subdomain: institution.tenant?.alias === 'vusa' ? 'www' : institution.tenant?.alias ?? 'www',
              lang: $page.props.app.locale,
            })"
            class="block"
          >
            <article
              class="group flex flex-col overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 p-5 ring-1 ring-zinc-200/50 transition-all duration-300 hover:ring-zinc-300 hover:shadow-lg dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600 h-full cursor-pointer"
            >
              <!-- Institution header - fixed height with vertical centering -->
              <div class="mb-5 min-h-[3.5rem] flex items-center">
                <h3 class="font-bold text-zinc-900 transition-colors group-hover:text-vusa-red dark:text-zinc-50 line-clamp-2 leading-snug">
                  {{ institution.name }}
                </h3>
              </div>

              <!-- Team grid - circular photos centered -->
              <div class="flex flex-wrap justify-center gap-5 flex-1 content-start">
                <div
                  v-for="{ user, duty } in getContactsWithDuties(institution).slice(0, 6)"
                  :key="`contact-${user.id}-${duty.id}`"
                  class="flex flex-col items-center text-center w-[72px]"
                >
                  <!-- Circular photo - bigger -->
                  <div class="mb-2">
                    <img
                      v-if="getContactPhoto(user, duty)"
                      :src="getContactPhoto(user, duty)"
                      :alt="user.name"
                      class="size-[72px] rounded-full object-cover ring-2 ring-white shadow-sm dark:ring-zinc-700"
                      style="object-position: 50% 20%"
                      loading="lazy"
                    >
                    <div 
                      v-else 
                      class="size-[72px] rounded-full flex items-center justify-center bg-zinc-200 ring-2 ring-white shadow-sm dark:bg-zinc-700 dark:ring-zinc-600"
                    >
                      <span class="text-base font-medium text-zinc-500 dark:text-zinc-400">{{ getInitials(user.name) }}</span>
                    </div>
                  </div>
                  <!-- Name only - bigger text -->
                  <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 line-clamp-2 leading-tight">
                    {{ user.name }}
                  </p>
                </div>
              </div>

              <!-- More contacts indicator -->
              <div 
                v-if="getContactsWithDuties(institution).length > 6" 
                class="mt-4 text-center text-sm text-zinc-400"
              >
                +{{ getContactsWithDuties(institution).length - 6 }} {{ $t('daugiau') }}
              </div>

              <!-- View more link -->
              <div class="mt-5 text-center text-sm font-medium text-vusa-red group-hover:underline">
                {{ $t('Plačiau') }} →
              </div>
            </article>
          </SmartLink>
        </div>
      </section>

      <!-- Single institution types (combined, no separate headers) -->
      <section v-if="singleInstitutionTypes.length > 0">
        <!-- Header for other institutions -->
        <div class="mb-5 flex items-center gap-3">
          <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-50">
            {{ $t('Kitos institucijos') }}
          </h2>
          <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
          <span class="text-sm text-zinc-400">
            {{ singleInstitutionTypes.length }}
          </span>
        </div>

        <!-- All single institutions in one grid -->
        <div class="grid gap-6 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
          <template v-for="institutionType in singleInstitutionTypes" :key="institutionType.id">
            <div
              v-for="institution in institutionType.institutions"
              :key="institution.id"
              class="flex flex-col gap-2"
            >
              <SmartLink
                :href="route('contacts.institution', {
                  institution: institution.id,
                  subdomain: institution.tenant?.alias === 'vusa' ? 'www' : institution.tenant?.alias ?? 'www',
                  lang: $page.props.app.locale,
                })"
                class="block flex-1"
              >
                <article
                  class="group flex flex-col overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 p-5 ring-1 ring-zinc-200/50 transition-all duration-300 hover:ring-zinc-300 hover:shadow-lg dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600 h-full cursor-pointer"
                >
                  <!-- Institution header - fixed height with vertical centering -->
                  <div class="mb-5 min-h-[3.5rem] flex items-center">
                    <h3 class="font-bold text-zinc-900 transition-colors group-hover:text-vusa-red dark:text-zinc-50 line-clamp-2 leading-snug">
                      {{ institution.name }}
                    </h3>
                  </div>

                  <!-- Team grid - circular photos centered -->
                  <div class="flex flex-wrap justify-center gap-5 flex-1 content-start">
                    <div
                      v-for="{ user, duty } in getContactsWithDuties(institution).slice(0, 6)"
                      :key="`contact-${user.id}-${duty.id}`"
                      class="flex flex-col items-center text-center w-[72px]"
                    >
                      <!-- Circular photo - bigger -->
                      <div class="mb-2">
                        <img
                          v-if="getContactPhoto(user, duty)"
                          :src="getContactPhoto(user, duty)"
                          :alt="user.name"
                          class="size-[72px] rounded-full object-cover ring-2 ring-white shadow-sm dark:ring-zinc-700"
                          style="object-position: 50% 20%"
                          loading="lazy"
                        >
                        <div 
                          v-else 
                          class="size-[72px] rounded-full flex items-center justify-center bg-zinc-200 ring-2 ring-white shadow-sm dark:bg-zinc-700 dark:ring-zinc-600"
                        >
                          <span class="text-base font-medium text-zinc-500 dark:text-zinc-400">{{ getInitials(user.name) }}</span>
                        </div>
                      </div>
                      <!-- Name only - bigger text -->
                      <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 line-clamp-2 leading-tight">
                        {{ user.name }}
                      </p>
                    </div>
                  </div>

                  <!-- More contacts indicator -->
                  <div 
                    v-if="getContactsWithDuties(institution).length > 6" 
                    class="mt-4 text-center text-sm text-zinc-400"
                  >
                    +{{ getContactsWithDuties(institution).length - 6 }} {{ $t('daugiau') }}
                  </div>

                  <!-- View more link -->
                  <div class="mt-5 text-center text-sm font-medium text-vusa-red group-hover:underline">
                    {{ $t('Plačiau') }} →
                  </div>
                </article>
              </SmartLink>
              <!-- Link to view all of this type -->
              <SmartLink
                v-if="institutionType.slug"
                :href="route('contacts.category', {
                  subdomain: 'www',
                  lang: $page.props.app.locale,
                  type: institutionType.slug,
                }) + '?all=1'"
                class="text-center text-xs text-zinc-500 hover:text-vusa-red transition-colors dark:text-zinc-400"
              >
                {{ pluralizeLithuanian(institutionType.title).visi }} {{ pluralizeLithuanian(institutionType.title).word }} →
              </SmartLink>
            </div>
          </template>
        </div>
      </section>
    </div>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <div class="relative">
        <div class="absolute inset-0 rounded-full bg-vusa-red/10 blur-2xl" />
        <div class="relative rounded-2xl bg-gradient-to-br from-zinc-100 to-zinc-50 p-6 ring-1 ring-zinc-200/50 dark:from-zinc-800 dark:to-zinc-900 dark:ring-zinc-700/50">
          <SearchIcon class="size-10 text-zinc-300 dark:text-zinc-600" />
        </div>
      </div>
      <h3 class="mt-6 text-xl font-bold text-zinc-900 dark:text-zinc-100">
        {{ $t('Rezultatų nerasta') }}
      </h3>
      <p class="mt-2 max-w-sm text-zinc-500 dark:text-zinc-400">
        {{ $t('Pabandykite pakeisti paieškos užklausą.') }}
      </p>
      <Button v-if="search" variant="outline" class="mt-6" @click="search = ''">
        {{ $t('Išvalyti paiešką') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { SearchIcon, GlobeIcon } from "lucide-vue-next";

import SmartLink from "@/Components/Public/SmartLink.vue";
import PadalinysSelector from "@/Components/Public/Nav/PadalinysSelector.vue";
import { pluralizeLithuanian } from "@/Utils/String";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { UserIcon, TypeIcon } from '@/Components/icons';

import { router } from "@inertiajs/vue3";

const $page = usePage();

const props = defineProps<{
  types: App.Entities.Type[];
  categoryType?: { id: number; slug: string; title: string; description?: string };
  showAllTenants?: boolean;
}>();

const search = ref<string>("");

// Set breadcrumbs for student representatives / category pages
usePageBreadcrumbs(() => {
  const items = [];
  
  // Main contacts link
  items.push(
    BreadcrumbHelpers.createRouteBreadcrumb(
      'Kontaktai',
      'contacts',
      {
        subdomain: 'www',
        lang: $page.props.app.locale
      },
      UserIcon
    )
  );
  
  // Current page - category title or default "Studentų atstovai"
  items.push(
    BreadcrumbHelpers.createBreadcrumbItem(
      props.categoryType?.title || 'Studentų atstovai',
      undefined,
      TypeIcon
    )
  );
  
  return BreadcrumbHelpers.publicContent(items);
});

// Check if this is the student representatives page (showing multiple categories)
const isStudentRepsPage = computed(() => !props.categoryType);

// Toggle all tenants view
const toggleAllTenants = () => {
  const url = new URL(window.location.href);
  if (props.showAllTenants) {
    url.searchParams.delete('all');
  } else {
    url.searchParams.set('all', '1');
  }
  router.visit(url.toString(), { preserveState: true, preserveScroll: true });
};

// Dynamic page title based on context
const pageTitle = computed(() => {
  if (props.categoryType) {
    return props.categoryType.title;
  }
  return 'Studentų atstovai';
});

// Dynamic page description based on context - use type description if available
const pageDescription = computed(() => {
  if (props.categoryType?.description) {
    return props.categoryType.description;
  }
  if (props.categoryType?.slug === 'pkp') {
    return 'Susipažinkite su VU SA programomis, klubais ir projektais.';
  }
  if (props.categoryType?.slug === 'studentu-atstovu-organas') {
    return 'Susipažinkite su studentų atstovais įvairiose VU institucijose ir darbo grupėse.';
  }
  return 'Susipažinkite su studentų atstovais įvairiose VU institucijose ir darbo grupėse.';
});

// Check if current tenant is main (pagrindinis/vusa)
const isMainTenant = computed(() => {
  const tenant = $page.props.tenant;
  return tenant?.alias === 'vusa' || tenant?.type === 'pagrindinis';
});

// Get current tenant name for display
const currentTenantName = computed(() => {
  const tenant = $page.props.tenant;
  if (!tenant || tenant.alias === 'vusa') return null;
  return tenant.shortname?.split(' ').pop() ?? null;
});

// Filter institutions by search query and sort types (multi-institution first)
const filteredTypesAndInstitutions = computed(() => {
  const filtered = props.types.map((type) => ({
    ...type,
    institutions: type.institutions.filter((institution) => {
      if (!search.value) return true;
      const query = search.value.toLowerCase();
      
      // Search in institution name
      if (institution.name.toLowerCase().includes(query)) return true;
      
      // Search in contact names
      const hasMatchingContact = institution.duties?.some((duty) =>
        duty.current_users?.some((user) =>
          user.name.toLowerCase().includes(query)
        )
      );
      
      return hasMatchingContact;
    }),
  }));

  // Sort: types with multiple institutions first, then by institution count descending
  return filtered.sort((a, b) => {
    const aMulti = a.institutions.length > 1 ? 1 : 0;
    const bMulti = b.institutions.length > 1 ? 1 : 0;
    if (aMulti !== bMulti) return bMulti - aMulti; // Multi-institution types first
    return b.institutions.length - a.institutions.length; // Then by count
  });
});

// Types with multiple institutions (show with headers)
const multiInstitutionTypes = computed(() => {
  return filteredTypesAndInstitutions.value.filter(type => type.institutions.length > 1);
});

// Single institution types (show without headers, combined)
const singleInstitutionTypes = computed(() => {
  return filteredTypesAndInstitutions.value.filter(type => type.institutions.length === 1);
});

// Check if there are any results
const hasResults = computed(() => {
  return filteredTypesAndInstitutions.value.some(
    (type) => type.institutions.length > 0
  );
});

// Total institutions count
const totalInstitutions = computed(() => {
  return filteredTypesAndInstitutions.value.reduce(
    (sum, type) => sum + type.institutions.length,
    0
  );
});

// Total contacts count
const totalContacts = computed(() => {
  return filteredTypesAndInstitutions.value.reduce(
    (sum, type) => sum + type.institutions.reduce(
      (instSum, inst) => instSum + getContactsWithDuties(inst).length,
      0
    ),
    0
  );
});

// Get contacts with their duties for an institution
const getContactsWithDuties = (institution: App.Entities.Institution) => {
  const result: Array<{ user: App.Entities.User; duty: App.Entities.Duty }> = [];

  institution.duties?.forEach((duty) => {
    duty.current_users?.forEach((user) => {
      result.push({ user, duty });
    });
  });

  return result;
};

// Get contact photo from user or duty pivot
const getContactPhoto = (user: App.Entities.User, duty: App.Entities.Duty) => {
  if (duty?.pivot?.additional_photo) return duty.pivot.additional_photo;
  if (user.pivot?.additional_photo) return user.pivot.additional_photo;
  return user.profile_photo_path ?? null;
};

// Get initials from name
const getInitials = (name: string) => {
  const parts = name.split(' ').filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return parts[0]?.substring(0, 2).toUpperCase() ?? '?';
};
</script>
