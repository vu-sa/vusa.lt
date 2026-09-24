<template>
  <RoleForm :role @submit:form="submit" />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import RoleForm from '@/Components/AdminForms/RoleForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{ role: App.Entities.Role }>();

usePageBreadcrumbs(() => BreadcrumbHelpers.adminForm('Rolės', 'roles.index', props.role.name));

function submit(form: unknown): void {
  const roleForm = form as InertiaForm<{ name: string }>;
  roleForm.defaults();
  roleForm.patch(route('roles.update', props.role.id), { preserveScroll: true });
}
</script>
