<template>
  <ResourceForm
    remember-key="CreateResource"
    :assignable-tenants
    :resource
    :categories
    @submit:form="(form) => (form as InertiaForm<Record<string, unknown>>).post(route('resources.store'))"
  />
</template>

<script setup lang="ts">
import { usePage, type InertiaForm } from '@inertiajs/vue3';

import ResourceForm from '@/Components/AdminForms/ResourceForm.vue';

import type { ResourceCreationTemplate } from '@/Components/AdminForms/resourceFormTypes';

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
  categories: App.Entities.ResourceCategory[];
}>();

const resource: ResourceCreationTemplate = {
  id: undefined,
  name: {
    lt: '',
    en: '',
  },
  description: {
    lt: '',
    en: '',
  },
  location: '',
  capacity: 1,
  resource_category_id: null,
  // If tenant_id is zero, then the form will be disabled (set in form).
  tenant_id: usePage().props.auth?.user.tenants[0]?.id ?? 0,
  is_reservable: true,
  media: [],
};
</script>
