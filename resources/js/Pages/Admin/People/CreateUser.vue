<template>
  <PageContent title="Naujas narys (-ė)" :heading-icon="Icons.USER">
    <UpsertModelLayout>
      <UserForm remember-key="CreateUser" :user :roles :tenants-with-duties :permissable-tenants
        @submit:form="(form) => form.post(route('users.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineProps<{
  roles: App.Entities.Role[];
  tenantsWithDuties: App.Entities.Tenant[];
  permissableTenants: App.Entities.Tenant[];
}>();

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Nariai', 'users.index', 'Naujas narys (-ė)', Icons.USER)
);

const user = {
  name: "",
  email: "",
  phone: null,
  current_duties: [],
  roles: [],
  profile_photo_path: null,
  pronouns: { lt: "", en: "" },
  show_pronouns: false,
};
</script>
