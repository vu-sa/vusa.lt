<template>
  <PageContent :title="membership.name.lt" :back-url="route('memberships.index')">
    <UpsertModelLayout>
      <MembershipForm can-import-memberships :membership :assignable-tenants
        @submit:form="(form) => form.patch(route('memberships.update', membership.id), { preserveScroll: true })"
        @delete="() => router.delete(route('memberships.destroy', membership.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import MembershipForm from "@/Components/AdminForms/MembershipForm.vue";

defineProps<{
  membership: App.Entities.Membership;
  assignableTenants: App.Entities.Tenant[];
}>();
</script>
