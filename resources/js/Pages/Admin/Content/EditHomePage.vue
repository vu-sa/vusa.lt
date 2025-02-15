<template>
  <AdminContentPage>
    <UpsertModelLayout>
      <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)">
        <RichContentFormElement v-model="form.parts" />
      </AdminForm>
    </UpsertModelLayout>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import RichContentFormElement from '@/Components/RichContentFormElement.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  tenant: App.Entities.Tenant;
}>();

const form = useForm(props.tenant.content);
</script>
