<template>
  <PageContent :title="news.title" :back-url="route('news.index')" :heading-icon="Icons.NEWS">
    <template #header>
      {{ news.title }}
    </template>
    <UpsertModelLayout>
      <template #card-header>
        Puslapio informacija
      </template>
      <NewsForm
        :news
        :other-lang-news="otherLangNews"
        :available-tags="availableTags"
        :submit-url="route('news.update', news.id)"
        submit-method="patch"
        enable-delete
        @submit:form="(form: any) => form.patch(route('news.update', news.id), { preserveScroll: true })"
        @delete="() => router.delete(route('news.destroy', news.id))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import NewsForm from "@/Components/AdminForms/NewsForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  news: App.Entities.News;
  otherLangNews?: App.Entities.News[];
  availableTags?: App.Entities.Tag[];
}>();

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(() => 
  BreadcrumbHelpers.adminForm('Naujienos', 'news.index', props.news.title, Icons.NEWS)
);
</script>
