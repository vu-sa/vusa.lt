<template>
  <div class="student-reps-page">
    <Head>
      <title>{{ `${pageHeadTitle} | ${$t('Kontaktai')}` }}</title>
      <meta v-if="pageDescription" name="description" :content="pageDescription">
    </Head>

    <!-- Page Title Band per editorial redesign -->
    <PageTitleBand
      :eyebrow="pageEyebrow"
      :lead="pageDescription || undefined"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>

      <template #default>
        {{ $t(pageTitle) }}
        <span v-if="isMainTenant && !showAllTenants" class="text-brand">· {{ $t('Centriniai') }}</span>
        <span v-else-if="currentTenantName && !showAllTenants" class="text-brand">· {{ currentTenantName }}</span>
        <span v-else-if="showAllTenants" class="text-brand">· {{ $t('Visi padaliniai') }}</span>
      </template>

      <template #actions>
        <SmartLink
          :href="route('contacts', { subdomain: 'www', lang: currentLocale })"
          :class="[
            'inline-flex h-10 items-center gap-1.5 border border-border bg-background px-4',
            'text-xs font-bold uppercase tracking-wider text-foreground hover:border-brand hover:text-brand transition-colors',
          ]"
        >
          <span>{{ $t('Visi kontaktai') }}</span>
          <IFluentArrowRight16Regular class="size-3.5" />
        </SmartLink>
      </template>
    </PageTitleBand>

    <!-- Main Content -->
    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <!-- Search and filter controls -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <!-- Search input -->
        <div class="relative min-w-0 flex-1">
          <IFluentSearch16Regular class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <input
            v-model="search"
            type="text"
            :placeholder="$t('Vardas arba institucija...')"
            :class="[
              'h-11 w-full border border-border bg-background pl-10 pr-9 text-sm text-foreground',
              'placeholder:text-muted-foreground/70 transition-colors focus:border-brand focus:outline-none',
            ]"
          >
          <button
            v-if="search"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            @click="search = ''"
          >
            <IFluentDismiss16Regular class="size-3.5" />
            <span class="sr-only">{{ $t('Išvalyti') }}</span>
          </button>
        </div>

        <!-- Padalinys filter & tenant toggle controls -->
        <div class="flex items-center gap-2 shrink-0">
          <PadalinysSelector id="padalinys" size="medium" main-tenant-label="Centriniai" />
          <button
            v-if="categoryType"
            type="button"
            :class="[
              'inline-flex h-11 items-center justify-center gap-1.5 border px-4 text-xs font-bold uppercase tracking-wider transition-colors shrink-0',
              showAllTenants
                ? 'border-brand bg-brand-fill text-brand-foreground'
                : 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
            ]"
            @click="toggleAllTenants"
          >
            <IFluentGlobe20Regular class="size-4" />
            <span>{{ $t('Visi') }}</span>
          </button>
        </div>
      </div>

      <!-- Stats counter bar -->
      <div class="flex items-center justify-between border-b border-border pb-3 pt-6">
        <div class="flex items-center gap-4 text-xs font-mono uppercase tracking-wider text-muted-foreground">
          <span>
            <strong class="font-bold text-foreground">{{ totalInstitutions }}</strong> {{ $t('institucijų') }}
          </span>
          <span>·</span>
          <span>
            <strong class="font-bold text-foreground">{{ totalContacts }}</strong> {{ $t('atstovų') }}
          </span>
        </div>
        <button
          v-if="search"
          type="button"
          class="text-xs font-semibold text-brand hover:underline"
          @click="search = ''"
        >
          {{ $t('Išvalyti paiešką') }}
        </button>
      </div>

      <!-- Results -->
      <div v-if="hasResults" class="mt-8 space-y-12">
        <!-- Types with multiple institutions (with headers) -->
        <section v-for="institutionType in multiInstitutionTypes" :key="institutionType.id">
          <!-- Type header -->
          <div class="mb-5 flex items-center justify-between gap-4 border-b border-border pb-3">
            <div class="flex items-baseline gap-3">
              <h2 class="u-display text-xl font-bold uppercase tracking-tight text-foreground sm:text-2xl">
                {{ $t(institutionType.title) }}
              </h2>
              <span class="text-xs font-mono text-muted-foreground">
                ({{ institutionType.institutions.length }})
              </span>
            </div>
            <!-- Link to category page when viewing multiple types -->
            <SmartLink
              v-if="isStudentRepsPage && institutionType.slug"
              :href="route('contacts.category', {
                subdomain: 'www',
                lang: currentLocale,
                type: institutionType.slug,
              }) + '?all=1'"
              class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-brand hover:underline"
            >
              <span>{{ $t('Žiūrėti visus') }}</span>
              <IFluentArrowRight16Regular class="size-3" />
            </SmartLink>
          </div>

          <!-- Institutions grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
            <StudentRepInstitutionCard
              v-for="institution in institutionType.institutions"
              :key="institution.id"
              :institution
            />
          </div>
        </section>

        <!-- Single institution types (combined, without separate headers) -->
        <section v-if="singleInstitutionTypes.length > 0">
          <div class="mb-5 flex items-baseline gap-3 border-b border-border pb-3">
            <h2 class="u-display text-xl font-bold uppercase tracking-tight text-foreground sm:text-2xl">
              {{ $t('Kitos institucijos') }}
            </h2>
            <span class="text-xs font-mono text-muted-foreground">
              ({{ singleInstitutionTypes.length }})
            </span>
          </div>

          <!-- All single institutions in one grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
            <template v-for="institutionType in singleInstitutionTypes" :key="institutionType.id">
              <div
                v-for="institution in institutionType.institutions"
                :key="institution.id"
                class="flex flex-col gap-2"
              >
                <StudentRepInstitutionCard
                  :institution
                  class="flex-1"
                />
                <SmartLink
                  v-if="institutionType.slug"
                  :href="route('contacts.category', {
                    subdomain: 'www',
                    lang: currentLocale,
                    type: institutionType.slug,
                  }) + '?all=1'"
                  class="mt-1 inline-flex items-center justify-center gap-1 text-center text-xs font-medium text-muted-foreground transition-colors hover:text-brand"
                >
                  <span>{{ pluralizeLithuanian(institutionType.title).visi }} {{ pluralizeLithuanian(institutionType.title).word }}</span>
                  <IFluentArrowRight16Regular class="size-3" />
                </SmartLink>
              </div>
            </template>
          </div>
        </section>
      </div>

      <!-- Empty state -->
      <div
        v-else
        class="mt-8 border border-border bg-card p-12 text-center"
      >
        <div class="mx-auto max-w-md space-y-3">
          <h3 class="text-base font-bold text-foreground">
            {{ $t('Rezultatų nerasta') }}
          </h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('Pabandykite pakeisti paieškos užklausą.') }}
          </p>
          <div v-if="search" class="pt-2">
            <button
              type="button"
              :class="[
                'inline-flex h-9 items-center justify-center border border-border bg-background px-4',
                'text-xs font-bold uppercase tracking-wider text-foreground hover:border-brand hover:text-brand transition-colors',
              ]"
              @click="search = ''"
            >
              {{ $t('Išvalyti paiešką') }}
            </button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { TenantType } from '@/Types/enums';
import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import StudentRepInstitutionCard from '@/Components/Cards/StudentRepInstitutionCard.vue';
import PadalinysSelector from '@/Components/Public/Nav/PadalinysSelector.vue';
import { pluralizeLithuanian } from '@/Utils/String';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { TypeIcon } from '@/Components/icons';
import IFluentSearch16Regular from '~icons/fluent/search-16-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';
import IFluentGlobe20Regular from '~icons/fluent/globe-20-regular';
import IFluentPeople16Regular from '~icons/fluent/people-16-regular';
import IFluentArrowRight16Regular from '~icons/fluent/arrow-right-16-regular';

const props = defineProps<{
  types: App.Entities.Type[];
  categoryType?: { id: number; slug: string; title: string; description?: string };
  showAllTenants?: boolean;
}>();

const page = usePage();
const search = ref<string>('');

const currentLocale = computed(() => {
  return (page.props.app as { locale?: string } | undefined)?.locale || 'lt';
});

// Dynamic page title based on context
const pageTitle = computed(() => {
  if (props.categoryType) {
    return props.categoryType.title;
  }
  return 'Studentų atstovai';
});

const pageHeadTitle = computed(() => {
  return $t(pageTitle.value);
});

// Set breadcrumbs with band placement
usePageBreadcrumbs(
  () => {
    return BreadcrumbHelpers.publicContent([
      BreadcrumbHelpers.createRouteBreadcrumb(
        $t('Kontaktai'),
        'contacts',
        {
          subdomain: 'www',
          lang: currentLocale.value,
        },
        IFluentPeople16Regular,
      ),
      BreadcrumbHelpers.createBreadcrumbItem(
        $t(pageTitle.value),
        undefined,
        TypeIcon,
      ),
    ]);
  },
  { placement: 'band' },
);

// Check if this is the student representatives page (showing multiple categories)
const isStudentRepsPage = computed(() => !props.categoryType);

// Toggle all tenants view
const toggleAllTenants = () => {
  const url = new URL(window.location.href);
  if (props.showAllTenants) {
    url.searchParams.delete('all');
  }
  else {
    url.searchParams.set('all', '1');
  }
  router.visit(url.toString(), { preserveState: true, preserveScroll: true });
};

// Dynamic page description based on context
const pageDescription = computed(() => {
  if (props.categoryType?.description) {
    return props.categoryType.description;
  }
  if (props.categoryType?.slug === 'pkp') {
    return 'Susipažinkite su VU SA programomis, klubais ir projektais.';
  }
  return 'Susipažinkite su studentų atstovais įvairiose VU institucijose ir darbo grupėse.';
});

// Check if current tenant is main (pagrindinis/vusa)
const isMainTenant = computed(() => {
  const tenant = page.props.tenant as { alias?: string; type?: string } | undefined;
  return tenant?.alias === 'vusa' || tenant?.type === TenantType.Pagrindinis;
});

// Get current tenant name for display
const currentTenantName = computed(() => {
  const tenant = page.props.tenant as { alias?: string; shortname?: string } | undefined;
  if (!tenant || tenant.alias === 'vusa') return null;
  return tenant.shortname?.split(' ').pop() ?? null;
});

// Computed eyebrow matching other Contacts pages
const pageEyebrow = computed(() => {
  const tenant = page.props.tenant as { name?: string } | undefined;
  if (props.categoryType) {
    return tenant?.name ? `${tenant.name} · ${$t('Kontaktai')}` : $t('Kontaktai');
  }
  return tenant?.name ? `${tenant.name} · ${$t('Studentų atstovai')}` : $t('Studentų atstovai');
});

// Helper to check matching contacts for search
const getContactsWithDuties = (institution: App.Entities.Institution) => {
  const result: Array<{ user: App.Entities.User; duty: App.Entities.Duty }> = [];
  institution.duties?.forEach((duty) => {
    duty.current_users?.forEach((user) => {
      result.push({ user, duty });
    });
  });
  return result;
};

// Filter institutions by search query and sort types (multi-institution first)
const filteredTypesAndInstitutions = computed(() => {
  const filtered = props.types.map(type => ({
    ...type,
    institutions: type.institutions.filter((institution) => {
      if (!search.value) return true;
      const query = search.value.toLowerCase();

      // Search in institution name
      if (institution.name.toLowerCase().includes(query)) return true;

      // Search in contact names
      const hasMatchingContact = institution.duties?.some(duty =>
        duty.current_users?.some(user =>
          user.name.toLowerCase().includes(query),
        ),
      );

      return hasMatchingContact;
    }),
  }));

  // Sort: types with multiple institutions first, then by institution count descending
  return filtered.sort((a, b) => {
    const aMulti = a.institutions.length > 1 ? 1 : 0;
    const bMulti = b.institutions.length > 1 ? 1 : 0;
    if (aMulti !== bMulti) return bMulti - aMulti;
    return b.institutions.length - a.institutions.length;
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
    type => type.institutions.length > 0,
  );
});

// Total institutions count
const totalInstitutions = computed(() => {
  return filteredTypesAndInstitutions.value.reduce(
    (sum, type) => sum + type.institutions.length,
    0,
  );
});

// Total contacts count
const totalContacts = computed(() => {
  return filteredTypesAndInstitutions.value.reduce(
    (sum, type) => sum + type.institutions.reduce(
      (instSum, inst) => instSum + getContactsWithDuties(inst).length,
      0,
    ),
    0,
  );
});
</script>
