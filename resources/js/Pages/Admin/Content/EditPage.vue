<template>
  <PageContent :title="page.title" :back-url="route('pages.index')" :heading-icon="PageIcon">
    <template #header>
      {{ page.title }}
    </template>
    <template #aside-header>
      <ActivityLogSheet subject-type="page" :subject-id="page.id" />
    </template>
    <ContentAnalyticsCard
      :id="page.id"
      type="page"
      :content-date="page.publish_time ?? page.created_at"
      class="mb-4" />
    <UpsertModelLayout>
      <template #card-header>
        <span>Puslapio informacija</span>
      </template>
      <PageForm
        :page
        :available-tags
        :other-lang-pages
        :submit-url="route('pages.update', page.id)"
        submit-method="patch"
        enable-delete
        @submit:form="submitForm"
        @delete="() => router.delete(route('pages.destroy', page.id))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import ContentAnalyticsCard from '@/Components/Analytics/ContentAnalyticsCard.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import PageForm from '@/Components/AdminForms/PageForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { PageIcon } from '@/Components/icons';

const props = defineProps<{
  // `descendant_ids` is server-computed (Page::descendantIds()), not a real relation.
  page: App.Entities.Page & { descendant_ids?: number[] };
  availableTags?: App.Entities.Tag[];
  otherLangPages: App.Entities.Page[];
}>();

function submitForm(form: InertiaForm<App.Entities.Page>): void {
  form.defaults();
  form.patch(route('pages.update', props.page.id), { preserveScroll: true });
}
</script>
