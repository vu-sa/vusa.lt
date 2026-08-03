<template>
  <AdminContentPage title="Redaguoti pagrindinį puslapį">
    <template #aside-header>
      <ActivityLogSheet subject-type="tenant" :subject-id="tenant.id" />
    </template>
    <UpsertModelLayout>
      <AdminForm :model="form" label-placement="top" @submit:form="handleFormSubmit">
        <RichContentFormElement v-model="form.parts" />
      </AdminForm>
    </UpsertModelLayout>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import RichContentFormElement from '@/Components/RichContent/RichContentFormElement.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';

const { tenant } = defineProps<{
  tenant: App.Entities.Tenant;
}>();

const form = useForm<App.Entities.Content>(tenant.content);

function handleFormSubmit() {
  form.post(route('tenants.updateMainPage', tenant.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
