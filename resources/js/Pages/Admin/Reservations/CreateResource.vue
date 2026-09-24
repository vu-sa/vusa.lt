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

import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import ResourceForm from '@/Components/AdminForms/ResourceForm.vue';
import { ResourceIcon } from '@/Components/icons';

export type ResourceMediaEntry
  = | { id: string | number; name: string; type: string; status: 'finished'; url: string }
    | { file: File; status: 'pending' };

export type ResourceCreationTemplate = Omit<
  App.Entities.Resource,
  'created_at' | 'updated_at' | 'deleted_at' | 'id' | 'name' | 'description'
> & {
  id: undefined;
  created_at?: string;
  updated_at?: string;
  name: Record<'lt' | 'en', string>;
  description: Record<'lt' | 'en', string>;
  media: ResourceMediaEntry[];
};

defineProps<{
  assignableTenants: Array<App.Entities.Tenant>;
  categories: App.Entities.ResourceCategory[];
}>();

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Ištekliai', 'resources.index', 'Naujas išteklius', ResourceIcon),
);

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
