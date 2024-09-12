<template>
  <PageContent :title="institution.name[$page.props.app.locale]" :back-url="route('institutions.index')"
    :heading-icon="Icons.INSTITUTION">
    <template #after-heading>
      <PreviewModelButton v-if="institution.alias" public-route="contacts.alias" :route-props="{
        institution: institution.alias,
        lang: $page.props.app.locale,
        subdomain: institution.tenant?.alias ?? 'www',
      }" />
      <PreviewModelButton v-else public-route="contacts.institution" :route-props="{
        institution: institution.id,
        lang: $page.props.app.locale,
        subdomain: institution.tenant?.alias ?? 'www',
      }" />
    </template>
    <UpsertModelLayout>
      <InstitutionForm enable-delete :assignable-tenants :institution :institution-types
        @submit:form="(form) => form.patch(route('institutions.update', institution.id), { preserveScroll: true })"
        @delete="() => router.delete(route('institutions.destroy', institution.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import InstitutionForm from "@/Components/AdminForms/InstitutionForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: Array<App.Entities.Type>;
  assignableTenants: Array<App.Entities.Tenant>;
}>();
</script>

<style>
.list-move {
  transition: all 0.5s ease;
}
</style>
