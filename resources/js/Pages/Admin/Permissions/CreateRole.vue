<template>
  <RoleForm remember-key="CreateRole" :role @submit:form="(form) => form.post(route('roles.store'))" />
</template>

<script setup lang="ts">
import RoleForm from '@/Components/AdminForms/RoleForm.vue';

const role = {
  name: '',
};

</script>
