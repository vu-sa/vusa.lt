<template>
  <PageContent :title="userName" :back-url="route('users.index')" :heading-icon="Icons.USER">
    <UpsertModelLayout>
      <UserForm :user :roles :tenants-with-duties :permissable-tenants
        @submit:form="(form) => form.patch(route('users.update', user.id), { preserveScroll: true })"
        @delete="() => router.delete(route('users.destroy', user.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import UserForm from '@/Components/AdminForms/UserForm.vue';

const props = defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  // TODO: don't return all duties from the controller immedixxately
  tenantsWithDuties: App.Entities.Tenant[];
  permissableTenants: App.Entities.Tenant[];
}>();

const userName = computed(() => {
  if (props.user.show_pronouns) {
    return `${props.user.name} (${props.user.pronouns[usePage().props.app.locale]})`;
  }
  else {
    return props.user.name;
  }
});

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm('Nariai', 'users.index', userName.value, Icons.USER),
);
</script>
