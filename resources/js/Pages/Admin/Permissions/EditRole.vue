<template>
  <RoleForm :role @submit:form="submit" />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import RoleForm from '@/Components/AdminForms/RoleForm.vue';

const props = defineProps<{ role: App.Entities.Role }>();

function submit(form: unknown): void {
  const roleForm = form as InertiaForm<{ name: string }>;
  roleForm.defaults();
  roleForm.patch(route('roles.update', props.role.id), { preserveScroll: true });
}
</script>
