<template>
  <RoleForm remember-key="CreateRole" :role @submit:form="(form) => form.post(route('roles.store'))" />
</template>

<script setup lang="ts">
import RoleForm from '@/Components/AdminForms/RoleForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const role = {
  name: '',
};

usePageBreadcrumbs(BreadcrumbHelpers.adminForm('Rolės', 'roles.index', 'Nauja rolė'));
</script>
