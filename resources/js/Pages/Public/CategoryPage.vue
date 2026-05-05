<template>
  <div>
    <section class="pt-8 last:pb-2">
      <article class="grid grid-cols-1 gap-x-12">
        <h1 class="col-span-full col-start-1 inline-flex gap-4">
          <span class="text-gray-900 dark:text-white">{{ category.name }}</span>
        </h1>
        <div class="flex max-w-prose flex-col gap-2 py-4 text-base leading-7">
          <p v-if="category.description" class="typography">
            {{ category.description }}
          </p>
          <div class="grid content-stretch gap-4 lg:grid-cols-2">
            <SmartLink v-for="page in category.pages" :key="page.id"
              :href="route('page', { permalink: page.permalink, lang: page.lang, subdomain: page.tenant.alias === 'vusa' ? 'www' : page.tenant.alias })">
              <PageCard :page />
            </SmartLink>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import PageCard from '../../Components/Cards/PageCard.vue';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import IFluentFolder16Regular from '~icons/fluent/folder-16-regular';

const props = defineProps<{
  category: App.Entities.Category;
}>();

// Set breadcrumbs for category page
usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      props.category.name || 'Kategorija',
      undefined,
      IFluentFolder16Regular,
    ),
  ]);
});
</script>
