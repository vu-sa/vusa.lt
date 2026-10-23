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
import { pickDefaultTenantId } from '@/Components/AdminForms/TenantSelectField.vue';

interface TopicOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  tenantOptions: Record<string, unknown>[];
  topicOptions: TopicOption[];
  defaultTenantId?: number | null;
  defaultLang?: 'lt' | 'en' | null;
}>();

const quickLink = {
  text: '',
  link: '',
  lang: props.defaultLang ?? 'lt',
  icon: '',
  is_important: false,
  tenant_id: props.defaultTenantId ?? pickDefaultTenantId(props.tenantOptions as unknown as App.Entities.Tenant[]),
};

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('quickLinks.store'));
}
</script>
