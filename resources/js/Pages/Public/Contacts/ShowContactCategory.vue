<template>
  <div class="contacts-category-page">
    <Head>
      <title>{{ `${$t('Kontaktai')}: ${typeTitle}` }}</title>
      <meta v-if="typeDescription" name="description" :content="typeDescription">
    </Head>

    <!-- Page Title Band per v0 redesign -->
    <PageTitleBand
      :eyebrow="pageEyebrow"
      :title="typeTitle"
      :lead="typeDescription || undefined"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
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
      <!-- Results Count Bar -->
      <div class="flex items-center justify-between border-b border-border pb-3">
        <div class="text-xs font-mono uppercase tracking-wider text-muted-foreground">
          {{ institutions.length }} {{ $t('search.results') }}
        </div>
      </div>

      <!-- Results Grid -->
      <div v-if="institutions.length > 0" class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        <NewInstitutionCard
          v-for="institution in institutions"
          :key="institution.id"
          :institution
        />
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="mt-6 border border-border bg-card p-12 text-center"
      >
        <div class="mx-auto max-w-md space-y-3">
          <h3 class="text-base font-bold text-foreground">
            {{ $t('No contacts available') }}
          </h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('There are currently no contacts available for this category.') }}
          </p>
          <div class="pt-2">
            <SmartLink
              :href="route('contacts', { subdomain: 'www', lang: currentLocale })"
              :class="[
                'inline-flex h-9 items-center justify-center border border-border bg-background px-4',
                'text-xs font-bold uppercase tracking-wider text-foreground hover:border-brand hover:text-brand transition-colors',
              ]"
            >
              {{ $t('Visi kontaktai') }}
            </SmartLink>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import NewInstitutionCard from '@/Components/Cards/NewInstitutionCard.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { TypeIcon } from '@/Components/icons';
import IFluentPeople16Regular from '~icons/fluent/people-16-regular';
import IFluentArrowRight16Regular from '~icons/fluent/arrow-right-16-regular';

const props = defineProps<{
  institutions: App.Entities.Institution[];
  type: App.Entities.Type;
}>();

const page = usePage();

const currentLocale = computed(() => {
  return (page.props.app as { locale?: string } | undefined)?.locale || 'lt';
});

// Computed eyebrow matching ShowContacts.vue
const pageEyebrow = computed(() => {
  const tenantName = (page.props.tenant as { name?: string } | undefined)?.name;
  return tenantName ? `${tenantName} · ${$t('Kontaktai')}` : $t('Kontaktai');
});

// Title & description helpers
const typeTitle = computed(() => {
  if (!props.type) return '';
  if (typeof props.type.title === 'string') {
    return $t(props.type.title);
  }
  const locale = (page.props.app as { locale?: string } | undefined)?.locale || 'lt';
  const localized = (props.type.title as Record<string, string> | undefined)?.[locale];
  return localized || Object.values((props.type.title as Record<string, string> | undefined) || {})[0] || props.type.slug;
});

const typeDescription = computed(() => {
  if (!props.type?.description) return '';
  if (typeof props.type.description === 'string') {
    return props.type.description;
  }
  const locale = (page.props.app as { locale?: string } | undefined)?.locale || 'lt';
  const localized = (props.type.description as Record<string, string> | undefined)?.[locale];
  return localized || Object.values((props.type.description as Record<string, string> | undefined) || {})[0] || '';
});

// Breadcrumbs with band placement
usePageBreadcrumbs(
  () => {
    const locale = (page.props.app as { locale?: string } | undefined)?.locale || 'lt';
    return BreadcrumbHelpers.publicContent([
      BreadcrumbHelpers.createRouteBreadcrumb(
        $t('Kontaktai'),
        'contacts',
        {
          subdomain: 'www',
          lang: locale,
        },
        IFluentPeople16Regular,
      ),
      BreadcrumbHelpers.createBreadcrumbItem(
        typeTitle.value,
        undefined,
        TypeIcon,
      ),
    ]);
  },
  { placement: 'band' },
);
</script>
