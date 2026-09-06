<template>
  <div class="category-page">
    <Head>
      <title>{{ category.name }}</title>
      <meta v-if="category.description" name="description" :content="category.description">
    </Head>

    <PageTitleBand
      :title="category.name"
      :lead="category.description || undefined"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <div v-if="category.pages && category.pages.length > 0">
        <ul class="divide-y divide-border border-y border-border">
          <li v-for="page in category.pages" :key="page.id">
            <SmartLink
              :href="route('page', { permalink: page.permalink, lang: page.lang, subdomain: resolveTenantSubdomain(page.tenant?.id) })"
              class="group flex items-center justify-between gap-4 py-3.5 px-2 transition-colors hover:bg-muted/40"
            >
              <span class="truncate font-medium text-foreground transition-colors group-hover:text-brand">
                {{ page.title }}
              </span>
              <span class="flex shrink-0 items-center gap-2">
                <IFluentChevronRight16Regular class="size-4 text-muted-foreground transition-colors group-hover:text-brand" />
              </span>
            </SmartLink>
          </li>
        </ul>
      </div>

      <div
        v-else
        class="border border-border bg-card p-12 text-center"
      >
        <p class="text-sm text-muted-foreground">
          {{ $t('Nėra puslapių') }}
        </p>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import IFluentChevronRight16Regular from '~icons/fluent/chevron-right-16-regular';

const props = defineProps<{
  category: {
    id: number;
    name: string;
    description?: string | null;
    pages: Array<{
      id: number;
      title: string;
      permalink: string;
      lang: string;
      category_id?: number;
      tenant_id?: number;
      tenant?: {
        id: number;
        alias: string;
      };
    }>;
  };
}>();

// Set breadcrumbs for category page
usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      props.category.name || 'Kategorija',
    ),
  ]);
}, { placement: 'band' });
</script>
