<template>
  <ResourceForm
    enable-delete
    :resource
    :categories
    :assignable-tenants
    :reservations="resource.reservations"
    @submit:form="handleResourceUpdate"
    @delete="() => router.delete(route('resources.destroy', resource.id))"
  />
</template>

<script setup lang="ts">
import { router, usePage, type InertiaForm } from '@inertiajs/vue3';

import ResourceForm from '@/Components/AdminForms/ResourceForm.vue';
import { ResourceIcon } from '@/Components/icons';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type { ResourceMediaEntry } from '@/Pages/Admin/Reservations/CreateResource.vue';

export type ResourceEditType = Omit<
  App.Entities.Resource,
  'created_at' | 'updated_at' | 'deleted_at' | 'name' | 'description'
> & {
  name: Record<'lt' | 'en', string>;
  description: Record<'lt' | 'en', string>;
  media: ResourceMediaEntry[];
  reservations?: App.Entities.Reservation[];
};

const { resource } = defineProps<{
  resource: ResourceEditType;
  categories: App.Entities.ResourceCategory[];
  assignableTenants: Array<App.Entities.Tenant>;
}>();

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm('Ištekliai', 'resources.index', resource.name[usePage().props.app.locale], ResourceIcon),
);

function handleResourceUpdate(form: unknown) {
  (form as InertiaForm<Record<string, unknown>>).transform(data => ({
    ...data,
    _method: 'patch',
  })).post(route('resources.update', resource.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
