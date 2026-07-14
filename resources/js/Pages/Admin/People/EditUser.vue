<template>
  <PageContent :title="userName" :back-url="route('users.index')" :heading-icon="UserIcon">
    <UpsertModelLayout>
      <UserForm :user :roles :tenants-with-duties :permissable-tenants
        @submit:form="onSubmit"
        @delete="() => router.delete(route('users.destroy', user.id))" />
    </UpsertModelLayout>
    <AccessChangeWarningDialog :open :report
      @update:open="open = $event" @confirm="confirm" @cancel="cancel" />
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import UserForm from '@/Components/AdminForms/UserForm.vue';
import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { UserIcon } from '@/Components/icons';

const props = defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  // TODO: don't return all duties from the controller immedixxately
  tenantsWithDuties: App.Entities.Tenant[];
  permissableTenants: App.Entities.Tenant[];
}>();

const { report, open, guardedSubmit, confirm, cancel } = useAccessChangeGuard();

const onSubmit = (form: any) =>
  guardedSubmit(acknowledge =>
    form
      .transform((data: Record<string, unknown>) => ({ ...data, acknowledge_access_change: acknowledge }))
      .patch(route('users.update', props.user.id), { preserveScroll: true, preserveState: true }),
  );

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
  BreadcrumbHelpers.adminForm('Nariai', 'users.index', userName.value, UserIcon),
);
</script>
