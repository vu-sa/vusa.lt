<template>
  <ResourceForm
    enable-delete
    :resource
    :categories
    :assignable-tenants
    :reservations="recentReservations"
    @submit:form="handleResourceUpdate"
    @delete="() => router.delete(route('resources.destroy', resource.id))"
  />
</template>

<script setup lang="ts">
import { router, usePage, type InertiaForm } from '@inertiajs/vue3';

import ResourceForm from '@/Components/AdminForms/ResourceForm.vue';
import type { ResourceBooking } from '@/Components/Reservations/ResourceBookingRow.vue';
import type { ResourceEditType } from '@/Components/AdminForms/resourceFormTypes';

const { resource } = defineProps<{
  resource: ResourceEditType;
  recentReservations: ResourceBooking[];
  categories: App.Entities.ResourceCategory[];
  assignableTenants: Array<App.Entities.Tenant>;
}>();

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
