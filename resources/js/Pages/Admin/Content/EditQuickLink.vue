<template>
  <QuickLinkForm
    :quick-link
    :tenant-options
    :topic-options
    enable-delete
    @submit:form="submitForm"
    @delete="deleteQuickLink"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import QuickLinkForm from '@/Components/AdminForms/QuickLinkForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { QuickLinkIcon } from '@/Components/icons';

interface TopicOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, unknown>[];
  topicOptions: TopicOption[];
}>();

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm('Greitosios nuorodos', 'quickLinks.index', props.quickLink.text, QuickLinkIcon),
);

function submitForm(form: unknown): void {
  const inertiaForm = form as InertiaForm<App.Entities.QuickLink>;
  inertiaForm.defaults();
  inertiaForm.patch(route('quickLinks.update', props.quickLink.id), { preserveScroll: true });
}

function deleteQuickLink(): void {
  router.delete(route('quickLinks.destroy', props.quickLink.id));
}
</script>
