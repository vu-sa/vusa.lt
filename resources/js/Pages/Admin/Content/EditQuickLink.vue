<template>
  <PageContent :title="quickLink.text" :back-url="route('quickLinks.index')" :heading-icon="QuickLinkIcon">
    <UpsertModelLayout>
      <QuickLinkForm :quick-link :tenant-options :type-options enable-delete
        @submit:form="(form) => form.patch(route('quickLinks.update', quickLink.id), { preserveScroll: true })"
        @delete="() => router.delete(route('quickLinks.destroy', quickLink.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import QuickLinkForm from '@/Components/AdminForms/QuickLinkForm.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { QuickLinkIcon } from '@/Components/icons';

defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
}>();
</script>
