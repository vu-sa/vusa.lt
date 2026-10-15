<template>
  <UserForm
    remember-key="CreateUser"
    :user
    :roles
    :tenants-with-duties
    :permissable-tenants
    @submit:form="(form) => (form as InertiaForm<Record<string, unknown>>).post(route('users.store'))"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import UserForm from '@/Components/AdminForms/UserForm.vue';

defineProps<{
  roles: App.Entities.Role[];
  tenantsWithDuties: App.Entities.Tenant[];
  permissableTenants: App.Entities.Tenant[];
}>();

const user = {
  name: '',
  email: '',
  phone: null,
  profile_photo_path: null,
  profile_photo_focal_point: null,
  pronouns: { lt: '', en: '' },
  show_pronouns: false,
} as unknown as App.Entities.User;
</script>
