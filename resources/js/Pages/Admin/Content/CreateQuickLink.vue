<template>
  <QuickLinkForm
    remember-key="CreateQuickLink"
    :tenant-options
    :topic-options
    :quick-link
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import QuickLinkForm from '@/Components/AdminForms/QuickLinkForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { QuickLinkIcon } from '@/Components/icons';

interface TopicOption {
  id: number;
  name: string;
  alias: string | null;
}

defineProps<{
  tenantOptions: Record<string, unknown>[];
  topicOptions: TopicOption[];
}>();

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Greitosios nuorodos', 'quickLinks.index', 'Nauja greitoji nuoroda', QuickLinkIcon),
);

const quickLink = {
  text: '',
  link: '',
  lang: 'lt',
  icon: '',
  is_important: false,
};

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('quickLinks.store'));
}
</script>
