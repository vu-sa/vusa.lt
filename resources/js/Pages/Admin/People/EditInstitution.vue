<template>
  <PageContent :title="$page.props.seo.title" :back-url="route('institutions.index')" :heading-icon="InstitutionIcon">
    <UpsertModelLayout>
      <InstitutionForm enable-delete :assignable-tenants :institution :institution-types
        :cadences :global-cadences :cadence-defaults
        :administrator-rosters :suggested-administrators
        @submit:form="(form) => form.patch(route('institutions.update', institution.id), { preserveScroll: true })"
        @delete="() => router.delete(route('institutions.destroy', institution.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import InstitutionForm from '@/Components/AdminForms/InstitutionForm.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { InstitutionIcon } from '@/Components/icons';
import type { CadenceRow } from '@/Components/Cadences';
import type { AdministratorRoster, AdministratorUser } from '@/Components/Institutions';

defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: Array<App.Entities.Type>;
  assignableTenants: Array<App.Entities.Tenant>;
  cadences: CadenceRow[];
  globalCadences: CadenceRow[];
  cadenceDefaults: { default_start_month_day: string; default_end_month_day: string };
  administratorRosters: AdministratorRoster[];
  suggestedAdministrators: AdministratorUser[];
}>();
</script>

<style>
.list-move {
  transition: all 0.5s ease;
}
</style>
