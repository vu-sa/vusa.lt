<template>
  <PageContent :title="banner.title" :back-url="route('banners.index')" :heading-icon="BannerIcon">
    <template #aside-header>
      <ActivityLogSheet subject-type="banner" :subject-id="banner.id" />
    </template>
    <UpsertModelLayout>
      <BannerForm :banner enable-delete
        @submit:form="(form) => form.patch(route('banners.update', banner.id), { preserveScroll: true })"
        @delete="() => router.delete(route('banners.destroy', banner.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import BannerForm from '@/Components/AdminForms/BannerForm.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { BannerIcon } from '@/Components/icons';

defineProps<{
  banner: App.Entities.Banner;
}>();
</script>
