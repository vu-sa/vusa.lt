<template>
  <PageContent title="Naujas turinio tipas" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="type">
      <TypeForm
        :content-types="contentTypes"
        :roles="roles"
        :type="typeTemplate"
        @submit:form="handleSubmit"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { typeTemplate } from "@/Types/formTemplates";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  contentTypes: Record<string, any>[];
  roles?: App.Entities.Role[];
}>();

const handleSubmit = (form: any) => {
  form.post(route("types.store"));
};
</script>
